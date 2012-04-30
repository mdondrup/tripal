<?php

/** 
 * @defgroup tripal_mviews_api Core Module Materalized Views API
 * @{
 * Provides an application programming interface (API) to manage materialized views in Chado.
 * The Perl-based chado comes with an interface for managing materialzed views.  This
 * API provides an alternative Drupal-based method.  
 * @}
 * @ingroup tripal_api
 */

/**
 * Add a materialized view to the chado database to help speed data access.
 *
 * @param $name 
 *   The name of the materialized view.
 * @param $modulename 
 *   The name of the module submitting the materialized view (e.g. 'tripal_library')
 * @param $mv_table 
 *   The name of the table to add to chado. This is the table that can be queried.
 * @param $mv_specs 
 *   The table definition 
 * @param $indexed 
 *   The columns that are to be indexed
 * @param $query 
 *   The SQL query that loads the materialized view with data
 * @param $special_index  
 *   function
 *
 * @ingroup tripal_mviews_api
 */
function tripal_add_mview ($name,$modulename,$mv_table,$mv_specs,$indexed,$query,$special_index,$comment=NULL){

   $record = new stdClass();
   $record->name = $name;
   $record->modulename = $modulename;
   $record->mv_schema = 'DUMMY';
   $record->mv_table = $mv_table;
   $record->mv_specs = $mv_specs;
   $record->indexed = $indexed;
   $record->query = $query;
   $record->special_index = $special_index;
   $record->comment = $comment;

   // add the record to the tripal_mviews table and if successful
   // create the new materialized view in the chado schema
   if(drupal_write_record('tripal_mviews',$record)){

      // drop the table from chado if it exists
      $previous_db = tripal_db_set_active('chado');  // use chado database
      if (db_table_exists($mv_table)) {
         $sql = "DROP TABLE $mv_table";
         db_query($sql);
      }
      tripal_db_set_active($previous_db);  // now use drupal database
      
      // now construct the indexes
      $index = '';
      if($indexed){
         // remove extra spaces and index field names
        $indexed = preg_replace("/\s+,/",",",$indexed);
        $indexed = preg_replace("/\s+\n/","\n",$indexed);
        $indexed = preg_replace("/,\n/","\n",$indexed);
        // add to the array of values
        $vals = preg_split("/[\n,]+/",$indexed);
        $index = '';
        foreach ($vals as $field){
           $index .= "CREATE INDEX idx_${mv_table}_${field} ON $mv_table ($field);";
        }
      }

      // add the table to the database
      $sql = "CREATE TABLE {$mv_table} ($mv_specs); $index"; 
      $previous_db = tripal_db_set_active('chado');  // use chado database
      $results = db_query($sql);
      tripal_db_set_active($previous_db);  // now use drupal database
      if($results){
         drupal_set_message(t("View '$name' created"));
      } else {
         // if we failed to create the view in chado then
         // remove the record from the tripal_jobs table
         $sql = "DELETE FROM {tripal_mviews} ".
                "WHERE mview_id = $record->mview_id";
         db_query($sql);
      }
   }
}
/**
 * Edits a materialized view to the chado database to help speed data access.
 *
 * @param $mview_id 
 *   The mview_id of the materialized view to edit
 * @param $name 
 *   The name of the materialized view.
 * @param $modulename 
 *   The name of the module submitting the materialized view (e.g. 'tripal_library')
 * @param $mv_table 
 *   The name of the table to add to chado. This is the table that can be queried.
 * @param $mv_specs 
 *   The table definition 
 * @param $indexed 
 *   The columns that are to be indexed
 * @param $query 
 *   The SQL query that loads the materialized view with data
 * @param $special_index  
 *   function
 *
 * @ingroup tripal_mviews_api
 */
function tripal_edit_mview ($mview_id,$name,$modulename,$mv_table,$mv_specs,$indexed,$query,$special_index,$comment){

   $record = new stdClass();
   $record->mview_id = $mview_id;
   $record->name = $name;
   $record->modulename = $modulename;
   $record->mv_schema = 'DUMMY';
   $record->mv_table = $mv_table;
   $record->mv_specs = $mv_specs;
   $record->indexed = $indexed;
   $record->query = $query;
   $record->special_index = $special_index;
   $record->last_update = 0;
   $record->status = '';
   $record->comment = $comment;

   // drop the table from chado if it exists
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
   $mview = db_fetch_object(db_query($sql));
   $previous_db = tripal_db_set_active('chado');  // use chado database
   if (db_table_exists($mview->mv_table)) {
      $sql = "DROP TABLE $mview->mv_table";
      db_query($sql);
   }
   tripal_db_set_active($previous_db);  // now use drupal database

   // update the record to the tripal_mviews table and if successful
   // create the new materialized view in the chado schema
   if(drupal_write_record('tripal_mviews',$record,'mview_id')){

      // drop the table from chado if it exists
      $previous_db = tripal_db_set_active('chado');  // use chado database
      if (db_table_exists($mv_table)) {
         $sql = "DROP TABLE $mv_table";
         db_query($sql);
      }
      tripal_db_set_active($previous_db);  // now use drupal database
      
      // now construct the indexes
      $index = '';
      if($indexed){
         // remove extra spaces and index field names
        $indexed = preg_replace("/\s+,/",",",$indexed);
        $indexed = preg_replace("/\s+\n/","\n",$indexed);
        $indexed = preg_replace("/,\n/","\n",$indexed);
        // add to the array of values
        $vals = preg_split("/[\n,]+/",$indexed);
        $index = '';
        foreach ($vals as $field){
           $index .= "CREATE INDEX idx_${mv_table}_${field} ON $mv_table ($field);";
        }
      }

      // recreate the view
      $sql = "CREATE TABLE {$mv_table} ($mv_specs); $index"; 
      $previous_db = tripal_db_set_active('chado');  // use chado database
      $results = db_query($sql);
      tripal_db_set_active($previous_db);  // now use drupal database
      if($results){
         drupal_set_message(t("View '$name' edited and saved.  All results cleared. Please re-populate the view."));
      } else {
         // if we failed to create the view in chado then
         // remove the record from the tripal_jobs table
         $sql = "DELETE FROM {tripal_mviews} ".
                "WHERE mview_id = $record->mview_id";
         db_query($sql);
      }
   }
}
/**
 * Retrieve the materialized view_id given the name
 *
 * @param $view_name
 *   The name of the materialized view
 *
 * @return
 *   The unique identifier for the given view
 *
 * @ingroup tripal_mviews_api
 */
function tripal_mviews_get_mview_id ($view_name){

   $sql = "SELECT * FROM {tripal_mviews} ".
          "WHERE name = '%s'";
   if(db_table_exists('tripal_mviews')){
      $mview = db_fetch_object(db_query($sql,$view_name));
	   if($mview){
	      return $mview->mview_id;
	   }
   }
   return 0;
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_mviews_action ($op,$mview_id,$redirect=0){
   global $user;
   $args = array("$mview_id");
   
   if(!$mview_id){
      return '';
   }

   // get this mview details
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
   $mview = db_fetch_object(db_query($sql));
   
   // add a job or perform the action based on the given operation
   if($op == 'update'){
      tripal_add_job("Populate materialized view '$mview->name'",'tripal_core',
         'tripal_update_mview',$args,$user->uid);
	}
   if($op == 'delete'){
	   // remove the mview from the tripal_mviews table
	   $sql = "DELETE FROM {tripal_mviews} ".
             "WHERE mview_id = $mview_id";
      db_query($sql);
		
	   // drop the table from chado if it exists
      $previous_db = tripal_db_set_active('chado');  // use chado database
      if (db_table_exists($mview->mv_table)) {
         $sql = "DROP TABLE $mview->mv_table";
         db_query($sql);
      }
      tripal_db_set_active($previous_db);  // now use drupal database
   }
   if($redirect){
      drupal_goto("admin/tripal/mviews");
   }
}
/**
* Update a Materialized View
*
* @param $mview_id
*   The unique identifier for the materialized view to be updated
*
* @return
*   True if successful, false otherwise
*
* @ingroup tripal_mviews_api
*/
function tripal_update_mview ($mview_id){
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = %d ";
   $mview = db_fetch_object(db_query($sql,$mview_id));
   if($mview){
      $previous_db = tripal_db_set_active('chado');  // use chado database
	   $results = db_query("DELETE FROM {$mview->mv_table}");
      $results = db_query("INSERT INTO $mview->mv_table ($mview->query)");
      tripal_db_set_active($previous_db);  // now use drupal database
      if($results){
         $sql = "SELECT count(*) as cnt FROM $mview->mv_table";
         $count = db_fetch_object(db_query($sql));
	      $record = new stdClass();
         $record->mview_id = $mview_id;
         $record->last_update = time();
         $record->status = "Populated with " . number_format($count->cnt) . " rows";
		   drupal_write_record('tripal_mviews',$record,'mview_id');
		   return 1;
      } else {
         # print and save the error message
	      $record = new stdClass();
         $record->mview_id = $mview_id;
         $record->status = "ERROR populating. See Drupal's recent log entries for details.";
         print $record->status . "\n";
		   drupal_write_record('tripal_mviews',$record,'mview_id');
	      return 0;
	  }
   }
}
/**
*
*
* @ingroup tripal_mviews_api
*/
function tripal_mview_report ($mview_id) {
   // get this mview details
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
   $mview = db_fetch_object(db_query($sql));

   // create a table with each row containig stats for
   // an individual job in the results set.

   $return_url = url("admin/tripal/mviews/");

   $output .= "<p><a href=\"$return_url\">Return to table of materialized views.</a></p>";
   $output .= "<br />";
   $output .= "<p>Details for <b>$mview->name</b>:</p>";
   $output .= "<br />";
   $output .= "<table class=\"border-table\">";
   if($mview->name){
      $output .= "  <tr>".
      "    <th>View Name</th>".
      "    <td>$mview->name</td>".
      "  </tr>";
   }   
   if($mview->modulename){
      $output .= "  <tr>".
      "    <th>Module Name</th>".
      "    <td>$mview->modulename</td>".
      "  </tr>";
   }
   if($mview->mv_table){
      $output .= "  <tr>".
      "    <th>Table Name</th>".
      "    <td>$mview->mv_table</td>".
      "  </tr>";
   }   
   if($mview->mv_specs){
      $output .= "  <tr>".
      "    <th>Table Field Definitions</th>".
      "    <td>$mview->mv_specs</td>".
      "  </tr>";
   }   
   if($mview->query){
      $output .= "  <tr>".
      "    <th>Query</th>".
      "    <td><pre>$mview->query</pre></td>".
      "  </tr>";
   }   
   if($mview->indexed){
      $output .= "  <tr>".
      "    <th>Indexed Fields</th>".
      "    <td>$mview->indexed</td>".
      "  </tr>";
   }   
   if($mview->special_index){
      $output .= "  <tr>".
      "    <th>Special Indexed Fields</th>".
      "    <td>$mview->speical_index</td>".
      "  </tr>";
   }   
   if($mview->last_update > 0){
      $update = format_date($mview->last_update);
   } else {
      $update = 'Not yet populated';
   }
   $output .= "  <tr>".
      "    <th>Last Update</th>".
      "    <td>$update</td>".
      "  </tr>";

   // build the URLs using the url function so we can handle installations where
   // clean URLs are or are not used
   $update_url = url("admin/tripal/mviews/action/update/$mview->mview_id");
   $delete_url = url("admin/tripal/mviews/action/delete/$mview->mview_id");
   $edit_url = url("admin/tripal/mviews/edit/$mview->mview_id");

   $output .= "<tr><th>Actions</th>".
              "<td> <a href='$update_url'>Populate</a>, ".
              "     <a href='$edit_url'>Edit</a>, ".
              "     <a href='$delete_url'>Delete</a></td></tr>";

   $output .= "</table>";

   return $output;
}
/**
*
*
* @ingroup tripal_mviews_api
*/
function tripal_mviews_report () {

   $header = array('','MView Name','Last Update','Status','Description','');
   $rows = array();

   $mviews = db_query("SELECT * FROM {tripal_mviews} ORDER BY name");  
   while($mview = db_fetch_object($mviews)){
      if($mview->last_update > 0){
         $update = format_date($mview->last_update);
      } else {
         $update = 'Not yet populated';
      }
      $rows[] = array(
         l('View',"admin/tripal/mviews/report/$mview->mview_id") ." | ".
         l('Edit',"admin/tripal/mviews/edit/$mview->mview_id") ." | ".
         l('Populate',"admin/tripal/mviews/action/update/$mview->mview_id"),
         $mview->name,
         $update,
         $mview->status,
         $mview->comment,
         l('Delete',"admin/tripal/mviews/action/delete/$mview->mview_id"),
      );
   }
   $rows[] = array(
      'data' => array( 
         array('data' => l('Create a new materialized view.',"admin/tripal/mviews/new"), 
               'colspan' => 6),
         )
   );
   $page = theme('table', $header, $rows);

   return $page;
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_mviews_form(&$form_state = NULL,$mview_id = NULL){

   if(!$mview_id){
      $action = 'Add';
   } else {
      $action = 'Edit';
   }

   // get this requested view
   if(strcmp($action,'Edit')==0){
      $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
      $mview = db_fetch_object(db_query($sql));

      # set the default values.  If there is a value set in the 
      # form_state then let's use that, otherwise, we'll pull 
      # the values from the database 
      $default_name = $form_state['values']['name'];
      $default_mv_table = $form_state['values']['mv_table'];
      $default_mv_specs = $form_state['values']['mv_specs'];
      $default_indexed = $form_state['values']['indexed'];
      $default_mvquery = $form_state['values']['mvquery'];
      $default_special_index = $form_state['values']['special_index'];
      $default_comment = $form_state['values']['cpmment'];
      if(!$default_name){
         $default_name = $mview->name;
      }
      if(!$default_mv_table){
         $default_mv_table = $mview->mv_table;
      }
      if(!$default_mv_specs){
         $default_mv_specs = $mview->mv_specs;
      }
      if(!$default_indexed){
         $default_indexed = $mview->indexed;
      }
      if(!$default_mvquery){
         $default_mvquery = $mview->query;
      }
      if(!$default_special_index){
         $default_special_index = $mview->special_index;
      }
      if(!$default_comment){
         $default_comment = $mview->comment;
      }
   }
   // Build the form
   $form['action'] = array(
      '#type' => 'value',
      '#value' => $action
   );
   $form['mview_id'] = array(
      '#type' => 'value',
      '#value' => $mview_id
   );
   $form['name']= array(
      '#type'          => 'textfield',
      '#title'         => t('View Name'),
      '#description'   => t('Please enter the name for this materialized view.'),
      '#required'      => TRUE,
      '#default_value' => $default_name,
      '#weight'        => 1
   );

   $form['mv_table']= array(
      '#type'          => 'textfield',
      '#title'         => t('Table Name'),
      '#description'   => t('Please enter the Postgres table name that this view will generate in the database.  You can use the schema and table name for querying the view'),
      '#required'      => TRUE,
      '#default_value' => $default_mv_table,
      '#weight'        => 3
   );
   $form['mv_specs']= array(
      '#type'          => 'textarea',
      '#title'         => t('Table Definition'),
      '#description'   => t('Please enter the field definitions for this view. Each field should be separated by a comma or enter each field definition on each line.'),
      '#required'      => TRUE,
      '#default_value' => $default_mv_specs,
      '#weight'        => 4
   );
   $form['indexed']= array(
      '#type'          => 'textarea',
      '#title'         => t('Indexed Fields'),
      '#description'   => t('Please enter the field names (as provided in the table definition above) that will be indexed for this view.  Separate by a comma or enter each field on a new line.'),
      '#required'      => FALSE,
      '#default_value' => $default_indexed,
      '#weight'        => 5
   );
   $form['mvquery']= array(
      '#type'          => 'textarea',
      '#title'         => t('Query'),
      '#description'   => t('Please enter the SQL statement used to populate the table.'),
      '#required'      => TRUE,
      '#default_value' => $default_mvquery,
      '#weight'        => 6
   );
/**
   $form['special_index']= array(
      '#type'          => 'textarea',
      '#title'         => t('View Name'),
      '#description'   => t('Please enter the name for this materialized view.'),
      '#required'      => TRUE,
      '#default_value' => $default_special_index,
      '#weight'        => 7
   );
*/
   $form['comment']= array(
      '#type'          => 'textarea',
      '#title'         => t('MView Description'),
      '#description'   => t('Optional.  Please provide a description of the purpose for this materialized vieww.'),
      '#required'      => FALSE,
      '#default_value' => $default_comment,
      '#weight'        => 8
   );
   if($action == 'Edit'){
      $value = 'Save';
   }
   if($action == 'Add'){
      $value = 'Add';
   }
   $form['submit'] = array (
     '#type'         => 'submit',
     '#value'        => t($value),
     '#weight'       => 9,
     '#executes_submit_callback' => TRUE,
   );

   $form['#redirect'] = 'admin/tripal/mviews';
   return $form;
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_mviews_form_submit($form, &$form_state){
   
   $action = $form_state['values']['action'];
   $mview_id = $form_state['values']['mview_id'];
   $name = $form_state['values']['name'];
   $mv_table = $form_state['values']['mv_table'];
   $mv_specs = $form_state['values']['mv_specs'];
   $indexed = $form_state['values']['indexed'];
   $query = $form_state['values']['mvquery'];
   $special_index = $form_state['values']['special_index'];
   $comment = $form_state['values']['comment'];

   if(strcmp($action,'Edit')==0){
      tripal_edit_mview($mview_id,$name, 'tripal_core',$mv_table, $mv_specs,$indexed,$query,$special_index,$comment);
   }
   else if(strcmp($action,'Add')==0){
      tripal_add_mview ($name, 'tripal_core',$mv_table, $mv_specs,$indexed,$query,$special_index,$comment);
   }
   else {
        drupal_set_message("No action performed.");
   }
   return '';
}
