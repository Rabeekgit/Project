<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 
 *
 * @package		VCUAE
 * @subpackage           Controller
 * @copyright	        Copyright (c) 2014
 * @since		Version 1.0
 * @purpose              To handle categories module for administrator
 */
class Category extends MY_Controlleredrdrdrdrdrdrd {
    echo "helloo";
    public $category_arr = array();
    public $rejected_category_keywords = array();
    
    function __construct() {
        parent::__construct();
        error_reporting(E_ALL);
        if ($this->nativesession->get('is_logged_in') == false) {
            $current_url['redirect'] = current_url();
            $this->nativesession->set($current_url);
            redirect('login');
		yfgyyfrfyfyff
        }
        $this->load->helper('category_helper');
        $this->load->library('my_pagination');
        $this->load->helper('filter_helper');
    }

    /**
     * list_categories method 
     *
     * List all categories records
     * @access	public
     * @return	view
     */
    public function list_categories() {
        user_auth_access(); //common helper function 

        $tablename = "category";
        $search = "";
        $where = array();   
        if ($this->input->get('turn_status')) {
            $turn_staus = $this->input->get('turn_status');
            if ($turn_staus == "off"){
                $data['turn_status'] = 'Off';
                $where['turn_on'] = 0;
            }
            else if ($turn_staus == "on"){
                $where['turn_on'] = 1;
                $data['turn_status'] = 'On';
            }else{
                $data['turn_status'] = 'All';
            }
        }else {
            $where['turn_on'] = 1;
            $data['turn_status'] = 'on';
        }

        
        $data['offset'] = ($this->input->post('offset')) ? $this->input->post('offset') : 0;
        $data['perPage'] = ($this->input->post('perPage')) ? $this->input->post('perPage') : 15000;
        $items = $this->master_model->getList("result_set", $field = 'CategoryName', $order = '', $data['offset'], $data['perPage'], $tablename, $search, $join = false, $where);
        if(count($items)){
            $data['categories'] = buildTree($items);
        }else{
            echo 0;die();
        }


        $data['add_link'] = base_url() . "category/add_edit_category/";
        $data['page_title'] = "Category Listing";
        if(IS_AJAX){
            $this->load->view('admin/category/category_list',$data);
        }else{
            $this->load->view('admin/head', $data);
            $this->load->view('admin/category/list_categories', $data);
            $this->load->view('admin/footer');
        }

    }

    /**
     * add_edit_category method 
     *
     * Add and Edit category View
     * @access	public
     * @param	int	id of category 
     * @return	view
     */
    public function add_edit_category($category_id = 0) {

           
        $cate_id = $category_id;
        $categoryList = array();
        $data = "";
        $tablename = "category";
        $data['cancel_link'] = base_url() . "category/list_categories/";
        $data['controller'] = $this->uri->segment(1);
        $data['method'] = $this->uri->segment(2);
		
		/* Category name and url field restriction start */
        $admin_id       = $this->nativesession->get('admin_id');
		  
		$this->db->select('resp_group_id');
		$this->db->where('admin_id', $admin_id);
		$this->db->where('resp_group_id', 20);
		$resp_group_id_query = $this->db->get('resp_usr_grp_mapping');
		$resp_group_id = $resp_group_id_query->result_array();
		$this->db->select('rg_name');
		$this->db->where('rg_id', $resp_group_id[0]['resp_group_id']);
		$resp_group_name_query = $this->db->get('responsible_groups');
		$resp_group_name = $resp_group_name_query->result_array();
		$data['resp_group_name'] = $resp_group_name[0]['rg_name'];
        /* Category name and url field restriction end */
        
        
        $mode = $this->uri->segment(3);
        if ($this->input->get('parent_id')) {
            $parent_cat = $this->input->get('parent_id');
        } else {
            $parent_cat = 0;
        }

        

        $config['base_url'] = base_url() . "category/add_edit_category";

        $tablename = "category";

        // Validate Category Keyword
        if(!empty($_POST["keywords"]))
            $this->validateCategoryKeywords($_POST["keywords"]);
        
        $seoprofile_data['prof_name'] = $category_data['CategoryName'] = $this->input->post('CategoryName');
        $category_data['parent'] = $this->input->post('parent');
        $category_data['fparent'] = $this->input->post('fparent');
        $category_data['CategoryDescription'] = $this->input->post('CategoryDescription');
        $category_data['head_description'] = $this->input->post('head_description');
        $category_data['LanguageID'] = $this->input->post('LanguageID');
        $category_data['Image'] = $this->input->post('Image');
        $category_data['IsActive'] = $this->input->post('IsActive');
        $category_data['Popular'] = $this->input->post('Popular');
        $category_data['istag'] = $this->input->post('istag');
        $category_data['issmart'] = $this->input->post('issmart');
        $category_data['alternative_text'] = $this->input->post('alternative_text');
        $category_data['keywords'] = $this->input->post('keywords');
        $seoprofile_data['display_url'] = $this->input->post('cattyp') . "/" . end(explode('/', $this->input->post('display_url')));
        $seoprofile_data['display_url'] = trim(str_replace(' ', '', $seoprofile_data['display_url']));
        
        
        $seoprofile_data['category_profile'] = 'Category';

        $sites = $this->input->post('sites');
        /* =============== Setting validation rules ================= */
        $this->form_validation->set_rules('CategoryName');
        
        
        //$this->form_validation->set_rules('keywords', 'Category Keywords', 'trim|required');
        
        $this->form_validation->set_error_delimiters('<span class="error">', '</span>');
        /* ========================================================= */

        //default seo profile of category
        $defaultProfile = $this->master_model->getList("single_set", $field = '', $order = '', $offset = '', $perpage = '', 'seoprofile', $search = '', false, "prof_name='Default' and id=789");

        /* [ Edit ] Functionality */
        if ($category_id != 0) {

            
            // Fetch Category Data :: Start --------------------
            $select = "b.CategoryName as parent_category,category.*, seoprofile.display_url,category_logs.date_time";
            $join   = array(
                'seoprofile' => 'seoprofile.cate_id = category.CategoryID|left',
                'category b' => 'category.parent = b.CategoryID|LEFT',  // Find Parent Category Name
				'category_logs' => 'category_logs.category_id = category.CategoryID|LEFT',
            );
            
            $where['category.CategoryID'] = $category_id;
            $categoryList                 = $this->master_model->getList("single_set", $field = '', $order = '', $offset = '', $perpage = '', $tablename, $search = '', $join, $where, $select);
            
            
            if ($categoryList){
                
                /* Assign parent_category and parent (parent_id) from inner array to outer array
                 * because this variables are already assigned in add_edit_category.php view
                 */
                $data["parent_category"]    = $categoryList["parent_category"];
                $data["parent_id"]          = $categoryList["parent"];          
                $data['categoryDetail']     = $categoryList;
                
            }    
            else { // if not found then 
                
                $this->session->set_flashdata('error', 'Not found any category that you want to edit');
                redirect("category/list_categories", "refresh");
            }            
            // Fetch Category Data :: End --------------------
            
            
                                        //            $where = array(
                                        //                "CategoryID" => $category_id
                                        //            );
            
            $seoprofile_where = array(
                "cate_id" => $category_id
            );


            //check form is posted on not
            if ($this->input->post('save_category') && $this->form_validation->run() == TRUE) {

                    $this->master_model->master_update($category_data, 'category', $where);
                    $isSeoProfile = $this->master_model->master_get_num_rows('seoprofile', $where = "cate_id = $category_id", $like = false); 
                    if($isSeoProfile){
                        $this->master_model->master_update($seoprofile_data, 'seoprofile', $where = "cate_id = $category_id");
                    }else{
                        $seoprofile_data['category_profile'] = $defaultProfile['category_profile'];
                        $seoprofile_data['page_title'] = $defaultProfile['page_title'];
                        $seoprofile_data['page_heading'] = $defaultProfile['category_profile'];
                        $seoprofile_data['sub_heading'] = $defaultProfile['sub_heading'];
                        $seoprofile_data['first_line'] = $defaultProfile['first_line'];
                        $seoprofile_data['meta_description'] = $defaultProfile['meta_description'];
                        $seoprofile_data['meta_keywords'] = $defaultProfile['meta_keywords'];
                        $seoprofile_data['banner_alt_text'] = $defaultProfile['banner_alt_text'];
                        $seoprofile_data['right_image_alt_text'] = $defaultProfile['right_image_alt_text'];
                        $seoprofile_data['description_title'] = $defaultProfile['description_title'];
                        $seoprofile_data['anchor_text'] = $defaultProfile['anchor_text'];
                        $seoprofile_data['cate_id'] = $category_id;
                       
                        $this->master_model->master_insert($seoprofile_data, 'seoprofile');
                        
                    }
                    
                //update profile
                // After Update :: cateogry allocation updation :yatin 
                $this->onUpdate_allocateCategories($category_id, $this->input->post(), $data['categoryDetail']);
                
                /* user log*/
                //User logs Entry
                $logs_array = array(
                    'entry_time' => $this->input->post('entry_time'),
                    'type' => 'category',
                    'action' => 'edit_category',
                    'category_id' => $cate_id,
                );
                $user_log_id = insert_user_logs($logs_array);
				
				 //Track changes code
                $from_field_column_name = $this->input->post('from_field_column_name');
                $from_field_old_value = $this->input->post('from_field_old_value');
                $from_field_name = $this->input->post('from_field_name');
                $from_field_column_name_explode = explode('|', $from_field_column_name);
                $from_field_old_value_explode = explode('|', $from_field_old_value);
                $from_field_name_explode = explode('|', $from_field_name);

                if (count($from_field_old_value_explode) > 0) {
                    foreach ($from_field_old_value_explode as $key => $old_value) {
                        if (isset($category_data[$from_field_column_name_explode[$key]])) {
                            $new_value = $category_data[$from_field_column_name_explode[$key]];
                        } else {
                            $new_value = 0;
                        }
                        if ($old_value != $new_value) {
                            if ($old_value == 0 && $new_value == 1) {
                                $old_value = "No";
                                $new_value = "Yes";
                            }
                            if ($old_value == 1 && $new_value == 0) {
                                $old_value = "Yes";
                                $new_value = "No";
                            }
                            $changes[$from_field_column_name_explode[$key]] = $old_value;

                            $insert_data = array(
                                'user_id' => $this->nativesession->get('admin_id'),
                                'user_log_id' => $user_log_id,
                                'action' => $from_field_name_explode[$key],
                                "category_id" => $category_id,
                                "old_value" => $old_value,
                                "new_value" => $new_value,
                                "date_time" => date("Y-m-d H:i:s")
                            );

                            $this->master_model->master_insert($insert_data, 'category_logs');
                        }
                    }
                }
                
                $this->session->set_flashdata('success', 'category Updated successfully');
                redirect("category/list_categories", "refresh");
            }

          



            $data['cat_id'] = $category_id;

            $default_pro_res = mysql_query("select * from  seoprofile   where prof_name='Default' and id=789");
        }
        //Adding functionality
        else {

            $data['parent_id'] = $parent_cat;
            //select 
            $parentCatDetail = $this->master_model->getList("single_set", $field = '', $order = '', $offset = '', $perpage = '', $tablename, $search = '', $join = FALSE, 'CategoryID = ' . $parent_cat);
            if ($parentCatDetail)
                $data['parent_category'] = $parentCatDetail['CategoryName'];
            else
                $data['parent_category'] = "";
            //check form is posted on not
            if ($this->input->post('save_category') && $this->form_validation->run() == TRUE) {

                $seoprofile_data['category_profile'] = $defaultProfile['category_profile'];
                $seoprofile_data['page_title'] = $defaultProfile['page_title'];
                $seoprofile_data['page_heading'] = $defaultProfile['category_profile'];
                $seoprofile_data['sub_heading'] = $defaultProfile['sub_heading'];
                $seoprofile_data['first_line'] = $defaultProfile['first_line'];
                $seoprofile_data['meta_description'] = $defaultProfile['meta_description'];
                $seoprofile_data['meta_keywords'] = $defaultProfile['meta_keywords'];
                $seoprofile_data['banner_alt_text'] = $defaultProfile['banner_alt_text'];
                $seoprofile_data['right_image_alt_text'] = $defaultProfile['right_image_alt_text'];
                $seoprofile_data['description_title'] = $defaultProfile['description_title'];
                $seoprofile_data['anchor_text'] = $defaultProfile['anchor_text'];

                
                    $new_category_id = $this->master_model->master_insert($category_data, 'category');
                    $seoprofile_data['cate_id'] = $new_category_id;
                    $this->master_model->master_insert($seoprofile_data, 'seoprofile');
                
                
                
                /* user log*/
                //User logs Entry
                $logs_array = array(
                    'entry_time' => $this->input->post('entry_time'),
                    'type' => 'category',
                    'action' => 'add_category',
                    'category_id' => $new_category_id,
                );
                insert_user_logs($logs_array);
                
                $this->session->set_flashdata('success', 'category Added successfully');
                redirect("category/list_categories", "refresh");
            }
        }

        $data['page_title'] = "Add/Edit Category";
        
        $this->load->view('admin/head', $data);
        $this->load->view('admin/category/add_edit_category', $data);
        $this->load->view('admin/footer');
    }
    
    
    /* get company by category
     * Function to get all company assigned to passed category and their sub category
     * @param int category id
     * return array();
     */
    
    public function get_company_by_category($category_id = ''){
        $category_id = $this->input->post('category_id');
        $select = "company.CompanyName, company.ContactPerson, company.CompanyID";
        $tablename = "company";
        $where['primary_cat'] = $category_id;
        $companyList = $this->master_model->get_master($tablename, $where, $join = FALSE, $order = 'ASC', $field = 'CompanyName', $select);
        echo json_encode($companyList);
        die;
    }
    /**
     * list_category_keyword method 
     *
     * List all the keywords for categories
     * @access	public
     * @return	view
     */
    public function list_category_keyword() {
        user_auth_access();
        $this->load->helper('date_time_helper');
        $this->load->library('inline_function');
        
        
        $config['base_url'] = base_url() . "category/list_category_keyword";
        $perpage = PAGINATION_PER_PAGE;
        $this->my_pagination->per_page = $perpage;
        $config = $this->my_pagination->pagination_config($config);

        $tablename = "category";
        $search = "";
        /* Search Module */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_value = htmlentities($_GET['search']);
            $search = array(
                'CategoryName' => $search_value,
                'keywords' => $search_value
            );
        }


        $config['total_rows'] = $this->master_model->getList("rows", $field = '', $order = '', $offset = '', $perpage, $tablename, $search);
        $this->my_pagination->initialize($config);
        $data['detail'] = $this->master_model->getList("result_set", $field = '', $order = '', $offset = '', $perpage, $tablename, $search);

        foreach ($data['detail'] as $key => $record) {
                 $table = "category_logs";
                 $select = "category_logs.*,adminuser.username,category.CategoryName as category_name";
                 $join['category'] = 'category.CategoryID = category_logs.category_id|INNER';
                 $join['adminuser'] = "adminuser.ID = " . $table . ".user_id|INNER";
                    $select = "$table.*";
                    $where = "$table.category_id = '" . $record['CategoryID'] . "' and (old_value!='' OR new_value!='') ORDER BY date_time desc";
                    
                 $track_changes = $this->master_model->get_master($table, $where, $join, FALSE, FALSE, $select);
                 
                 
                 $data['detail'][$key]['track_changes'] = $track_changes;   
                 
                 
                 
        }
        
        
        $data['base_url'] = $config['base_url'];
        $data['add_link'] = base_url() . "category/edit_category_keyword/";
        $data['page_title'] = "List all the keywords for categories";
        if (IS_AJAX) {
            $data['is_ajax'] = '1';
            $this->load->view('admin/category/list_category_keyword', $data);
        } else {
            $this->load->view('admin/head', $data);
            $this->load->view('admin/category/list_category_keyword', $data);
            $this->load->view('admin/footer');
        }
    }

    /**
     * edit_category_keyword
     *
     * Add OR Edit category keyword
     * @access	public
     * @param	int	id of category 
     * @return	view
     */
    public function edit_category_keyword($id = "") {
        user_auth_access();
        if (empty($id)) {
            redirect('category/list_category_keyword');
            exit;
        }
        $data = "";
        $tablename = "category";
        $data['cancel_link'] = base_url() . "category/list_category_keyword/";
        $data['controller'] = $this->uri->segment(1);
        $data['method'] = $this->uri->segment(2);
        $data['page_title'] = "Add/Edit category keyword";
        $this->form_validation->set_rules('keywords', 'Keywords', 'trim');
        
        // Validate Category Keyword
        if(!empty($_POST["keywords"]))
            $this->validateCategoryKeywords($_POST["keywords"]);
        
        $this->form_validation->set_error_delimiters('<br /><span class="error">', '</span>');
        //Edit Functionality
        if (!empty($id)) {

            if ($this->form_validation->run() == TRUE) {
                $update_data = array(
                    'keywords' => set_value('keywords')
                );
                $where = array(
                    'CategoryID' => $id
                );
                //Multiple site update function
                multi_site_connect_update($tablename, $update_data, $where);
                
                
                
                
                /* user log*/
                //User logs Entry
                $logs_array = array(
                    'entry_time' => $this->input->post('entry_time'),
                    'type' => 'category',
                    'action' => 'edit_category',
                    'category_id' => $id,
                );
                $user_log_id = insert_user_logs($logs_array);
                
                
                //Track changes code
             
                $old_value = $this->input->post('old_value');
                $new_value = $this->input->post('keywords');
                       
                        if ($old_value != $new_value) {
                            $insert_data['user_log_id'] = $user_log_id;
                            $insert_data['user_id'] = $this->nativesession->get('admin_id');
                            $insert_data['action'] = 'Keywords';
                            $insert_data['category_id'] = $id;
                            $insert_data['old_value'] = $old_value;
                            $insert_data['new_value'] =  $new_value;                                   
                         
                            $this->master_model->master_insert($insert_data, 'category_logs');
                        }
                
                         
                
                
                $this->session->set_flashdata('success', 'Keywords updated successfully');
                redirect('category/list_category_keyword/');
            }

            $where = array(
                "CategoryID" => $id
            );

            $single_result = $this->master_model->getList("single_set", $field = '', $order = '', $offset = '', $perpage = '', $tablename, $search = '', $join = FALSE, $where);

            $data['single_result'] = $single_result;
            $data['id'] = $id;
        }

        $this->load->view('admin/head', $data);
        $this->load->view('admin/category/edit_category_keyword', $data);
        $this->load->view('admin/footer');
    }

    /**
     * category_redirection 
     *
     * list of category redirection
     * @access	public
     * @return	view
     */
    public function category_redirection() {
        user_auth_access();
        $this->load->helper('date_time_helper');
        $config['base_url'] = base_url() . "category/category_redirection";
        $perpage = PAGINATION_PER_PAGE;
        $this->my_pagination->per_page = $perpage;
        $config = $this->my_pagination->pagination_config($config);
        $tablename = "category_redirection";
        $search = "";
        /* Search Module */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_value = htmlentities($_GET['search']);
            $search = array(
                'display_url' => $search_value,
                'target_url' => $search_value,
            );
        }


        $config['total_rows'] = $this->master_model->getList("rows", $field = '', $order = '', $offset = '', $perpage, $tablename, $search);
        $this->my_pagination->initialize($config);
        $data['detail'] = $this->master_model->getList("result_set", $field = 'dateadded', $order = '', $offset = '', $perpage, $tablename, $search);

        $data['base_url'] = $config['base_url'];
        $data['add_link'] = base_url() . "category/add_category_redirection";
        if (IS_AJAX) {
            $data['is_ajax'] = '1';
            $this->load->view('admin/category/category_redirection', $data);
        } else {
            $this->load->view('admin/head', $data);
            $this->load->view('admin/category/category_redirection', $data);
            $this->load->view('admin/footer');
        }
    }

    /**
     * add_category_redirection
     *
     * add / update category_redirection
     * @access	public
     * @return	view
     */
    public function add_category_redirection() {
        user_auth_access();
        $this->load->model('category_model');
        $data = "";
        $tablename = "category_redirection";
        $data['cancel_link'] = base_url() . "category/category_redirection";
        $data['controller'] = $this->uri->segment(1);
        $data['method'] = $this->uri->segment(2);


        $this->form_validation->set_rules('redirect_from_url', 'Redirect from url', 'required|trim');
        $this->form_validation->set_rules('redirect_to_url', 'Redirect to url', 'required|trim');

        if ($this->form_validation->run() == TRUE) {
            $redirect_from_prefix = $this->input->post('redirect_from_prefix');
            $redirect_to_prefix = $this->input->post('redirect_to_prefix');
            $display_url = $this->input->post('redirect_from_url');
            $target_url = $this->input->post('redirect_to_url');

            $redirect_from_url = $data['display_url'] = $redirect_from_prefix . '/' . $display_url;
            $redirect_to_url = $data['target_url'] = $redirect_to_prefix . '/' . $target_url;

            if ($this->category_model->check_existing_url($tablename, $redirect_from_url)) {
                $where = array('display_url' => $redirect_from_url);
                $update_data['target_url'] = $redirect_to_url;
                $this->master_model->master_update($update_data, $tablename, $where);
                $msg = "Category redirection Updated successfully";
            } else {
                
                
                $this->master_model->insertData($tablename, $data);
                $msg = "Category redirection added successfully";
            }

            $this->session->set_flashdata('success', $msg);
            redirect('category/category_redirection');
        }
        $this->load->view('admin/head', $data);
        $this->load->view('admin/category/add_category_redirection', $data);
        $this->load->view('admin/footer');
    }

    /**
     * delete_category_redirection
     * @access	public
     * @param   int  (id of category)     
     * @return	view
     */
    public function delete_category_redirection($id) {
        $tablename = "category_redirection";
        $where = "id = " . $id . "";
        $this->master_model->master_delete($tablename, $where);
        $this->session->set_flashdata('success', 'Deleted successfully');
        redirect('category/category_redirection');
    }

    /**
     * sequence_category_listing
     *
     * @access	public
     * @param	string	search key
     * @return	view
     */
    public function sequence_category_listing($search_key = 'a') {
        user_auth_access();
        $config['base_url'] = base_url() . "category/sequence_category_listing";
        $perpage = 200;
        $this->my_pagination->per_page = $perpage;
        $config = $this->my_pagination->pagination_config($config);
        $tablename = "category";
        $search = "";
        /* Search Module */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_value = htmlentities($_GET['search']);
            $search = array(
                'CategoryName' => $search_value,
            );
        }

        if (isset($_GET['search_key']) && !empty($_GET['search_key'])) {
            $search_key = $_GET['search_key'];
        }

        $select = "category.CategoryID,
       category.CategoryName,
       category.CategoryDescription,
       category.Image,
       category.IsActive, category.Popular , category.categorySequence,
       CASE category.IsActive 
       WHEN 1 THEN 'Enable' 
       WHEN 0 THEN 'Disable' 
       ELSE null END as 'status',	  
       CASE category.LanguageID 
       WHEN 1 THEN 'English' 
       ELSE language.LanguageName END as LanguageName,
       CASE category.LanguageID WHEN 1 THEN category.LanguageID 
       ELSE language.LanguageID END as LanguageID";

        $join = array(
            'language' => 'language.LanguageID = category.LanguageID|left'
        );

        $where = "(category.categorySequence<20 or category.CategoryName LIKE '$search_key%')";
        $field = "c.categorySequence,c.CategoryName";

        $config['total_rows'] = $this->master_model->getList("rows", $field = '', $order = '', $offset = '', $perpage, $tablename, $search, $join, $where, $select);
        $this->my_pagination->initialize($config);
        $data['detail'] = $this->master_model->getList("result_set", $field = 'category.categorySequence,category.CategoryName', $order = 'ASC', $offset = '', $perpage, $tablename, $search, $join, $where, $select);
        //echo $this->db->last_query();
        $meta_data['page_title'] = "Category Sequence";
        
        $data['base_url'] = $config['base_url'];
        if (IS_AJAX) {
            $data['is_ajax'] = '1';
            $this->load->view('admin/category/sequence_category_listing', $data);
        } else {
            $this->load->view('admin/head', $data);
            $this->load->view('admin/category/sequence_category_listing', $data);
            $this->load->view('admin/footer');
        }
    }

    /**
     * @method category_sponsored_coupons
     * @Description To list all sponsored coupons of category
     * @access	public
     * @return	view
     */
    public function category_sponsored_coupons() {
        user_auth_access(); //common helper function 
        $config['base_url'] = base_url() . "category/category_sponsored_coupons";
        $perpage = PAGINATION_PER_PAGE;
        $this->my_pagination->per_page = $perpage;
        $config = $this->my_pagination->pagination_config($config);

        $tablename = "sponsored_company";
        $select = "sponsored_company.*, company.CompanyName ";

        $search = "";
        /* Search Module */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_value = htmlentities($_GET['search']);
            $search = array(
                'company.CompanyName' => $search_value,
            );
        }

        /* Joins Module */
        $join = array(
            'company' => 'sponsored_company.CompanyID = company.CompanyID|left',
        );

        $where = "sponsored_company.EndDate >= CURDATE() ";
        //echo $where;
        $config['total_rows'] = $this->master_model->getList("rows", $field = '', $order = '', $offset = '', $perpage, $tablename, $search, $join, $where, $select, $distinct = TRUE);
        $this->my_pagination->initialize($config);
        $data['detail'] = $this->master_model->getList("result_set", $field = 'clicked', $order = 'DESC', $offset = '', $perpage, $tablename, $search, $join, $where, $select, $distinct = TRUE);
        $data['total_rows'] = $config['total_rows'];

        /* Get the filter data */
        $data['base_url'] = $config['base_url'];
        $meta_data['page_title'] = "Category Sponsored Coupon";

        if (IS_AJAX) {
            $data['is_ajax'] = '1';
            $this->load->view('admin/category/category_sponsored_coupons', $data);
        } else {
            $this->load->view('admin/head', $meta_data);
            $this->load->view('admin/category/category_sponsored_coupons', $data);
            $this->load->view('admin/footer');
        }
    }

    /**
     * @method sponsored_coupon_form
     * @Description To Display the form of sponsored coupon in add or edit mode. In edit mode coupon id is passed in 4th parameter in url
     * @access	public
     * @return	view
     */
    public function sponsored_coupon_form() {
        user_auth_access(); //common helper function 
        $this->load->model('category_model');
        $data['cancel_link'] = base_url() . "category/category_sponsored_coupons";
        $data['spons_coupon'] = array(); //Initializing array
        $url_data = explode('/', uri_string());
        $mode = $url_data[2]; //set the mode passed in 3nd parameter in url.
        if ($mode == "edit") {//If form in edit mode getting all relaive data to coupon
            $coupon_id = $url_data[3]; //get coupon id passed in 4th parameter in url
            $select = "sponsored_company.*, company.CompanyName ";
            $table = "sponsored_company";
            $where = "Id = " . $coupon_id;
            $join = array(
                'company' => 'sponsored_company.CompanyID = company.CompanyID|left',
            );
            $spons_coupon = $this->master_model->getList("result_set", $field = 'clicked', $order = 'DESC', $offset = '', '1', $table, $search = false, $join, $where, $select, $distinct = TRUE);
            if (count($spons_coupon) > 0)
                $data['spons_coupon'] = $spons_coupon[0];
            else
                $data['spons_coupon'] = 0;


            /* Get category assigned to sponsored coupon */
            $select = "sponsored_category.CategoryID, category.CategoryName";
            $table = "sponsored_category";
            $where = "sponsored_category.CouponID = " . $coupon_id;
            $join = array(
                'category' => 'category.CategoryID = sponsored_category.CategoryID|left',
            );


            $data['selected_categories_array'] = $selected_categories_array = $this->master_model->get_master($table, $where, $join = $join, $order = false, $field = false, $select);
            
            $data['category_listing'] = $this->category_model->category_listing(1);
            if ($selected_categories_array && count($selected_categories_array) > 0) {
                foreach ($selected_categories_array as $category) {
                    $selected_category[] = $category['CategoryID'];
                }
                $data['selected_category'] = $selected_category;
            } else {
                $data['selected_category'] = array();
            }
        }

        
        
        $data['category_arr'] = $this->master_model->get_master("category", $where = FALSE, $join = FALSE, $order = false, $field = false, "CategoryID, CategoryName");
        $data['mode'] = $mode;
        if ($mode == "edit") {
            $meta_data['page_title'] = "Edit Category Sponsored Coupon";
        } else {
            $meta_data['page_title'] = "Add Category Sponsored Coupon";
        }
        $this->load->view('admin/head', $meta_data);
        $this->load->view('admin/category/sponsored_coupon_form', $data);
        $this->load->view('admin/footer');
    }

    /**
     * @method delete_sponsored_couon
     * @Description To delete sponsored coupon, coupon id is passed by get metohd. After deleting redirecting page to sponsored coupon listing page.
     * @access	public
     */
    public function delete_sponsored_couon() {
        $coupon_id = $this->input->get('coupon_id');
        $this->master_model->master_delete("sponsored_category", " CouponID = " . $coupon_id);
        $this->master_model->master_delete("sponsored_company", " Id = " . $coupon_id);
        $this->session->set_flashdata('success', 'Coupon deleted successfully');
        redirect('/category/category_sponsored_coupons', 'refresh');
    }

    /**
     * @method save_sponsored_couon
     * @Description To save sponsored coupon in database i.e. saving the sponsored coupon form data.After saving redirecting page to sponsored coupon listing page with message on session.
     * @access	public
     */
    public function save_sponsored_couon() {
        $mode = "add"; /* Intialize varialbe mode with default value add */

        /* Getting post values */
       
        $categories = $this->input->post('categories');
        
       
        $mode = $this->input->post('mode');
        $Id = $this->input->post('Id'); /* Sponsored coupon id */
        $company_name = $this->input->post('company_name');

        /* Assign all coupons posted data in array */
        $coupon_arr['CompanyID'] = $this->input->post('companyID');
        $coupon_arr['LogoColor'] = $this->input->post('logo_bg_color');
        $coupon_arr['Title'] = $this->input->post('title');
        $coupon_arr['TitleColor'] = $this->input->post('title_color');
        $coupon_arr['Description'] = $this->input->post('description');
        $coupon_arr['DescriptionColor'] = $this->input->post('description_font_color');
        $coupon_arr['link'] = $this->input->post('link');
        $coupon_arr['EndDate'] = date("y-m-d", strtotime($this->input->post('end_date')));

        /* Set the edit mode dynamic path, to redirect back in forma page if validation get any error */
        if ($mode == "edit") {
            $edit_id = "/edit/" . $this->input->post('Id');
        } else {
            $edit_id = "";
        }
        /* validate empty fields and redirecting to sponsored coupon form, if get any empty fields */
        if ($coupon_arr['CompanyID'] == "") {
            $this->session->set_flashdata('error', 'company name can not be blank !');
            redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
        } else if (count($categories) == 0) {
            $this->session->set_flashdata('error', 'PleaseAdd a company !');
            redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
        } else if ($coupon_arr['link'] == "") {
            $this->session->set_flashdata('error', 'link to open can not be blank ! !');
            redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
        } else if ($coupon_arr['Title'] == "") {
            $this->session->set_flashdata('error', 'title can not be blank !');
            redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
        } else if ($coupon_arr['Description'] == "") {
            $this->session->set_flashdata('error', 'description can not be blank !');
            redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
        }

        foreach ($coupon_arr as $coupon) {
            if ($coupon == "") {
                $this->session->set_flashdata('error', 'All Fields are mandatory !');
                if ($mode = "edit") {
                    $edit_id = "/edit/" . $this->input->post('Id');
                } else {
                    $edit_id = "";
                }
                redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
            }
        }

        /* Uploading sponsored logo */
        if ($_FILES['logo']['name'] != '') {
            // Uploading image on the local server
            $ext = end(explode(".", $_FILES['logo']['name']));
            $new_file_name = 'sponsered_company_coupon' . rand(1000, 9999) . '.' . $ext;
            $config['file_name'] = $new_file_name;
            $config['upload_path'] = IMAGES_PATH . "sponsored_company/";
            $config['allowed_types'] = 'gif|jpg|png|jpeg';

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload("logo")) {
                $error = $this->upload->display_errors();
                $this->session->set_flashdata('error', "Error on image uploading : " . $error);
                if ($mode = "edit") {
                    $edit_id = "/edit/" . $this->input->post('Id');
                } else {
                    $edit_id = "";
                }
                redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
            } else {

				$this->load->library('image_lib');		
                $logo_path = $_FILES["logo"]["name"];

				//resizing image


				$newImage = 'sponsered_company_coupon' . rand(1000, 9999) . '.jpg';

				$config2 = array(
                'image_library' => 'gd2',
                'source_image' => $this->upload->upload_path.$this->upload->file_name,
                'new_image' => $this->upload->upload_path.$newImage,
                'maintain_ratio' => false,
                'width' => 125,
                'height' => 85,
				
				);

			
	            $this->image_lib->initialize($config2);
		        if (! $this->image_lib->resize()){
					$this->session->set_flashdata('error', "Error on image uploading : " . $error);
							if ($mode = "edit") {
								$edit_id = "/edit/" . $this->input->post('Id');
							} else {
								$edit_id = "";
							}
							redirect('category/sponsored_coupon_form' . $edit_id, 'refresh');
				}
                $coupon_arr['Logo'] = $newImage;
				$this->image_lib->clear();
				

				
            }
        }
        /* update the table, if form in edit mode */
        if ($mode == "edit") {
            $where = array();
            $where['Id'] = $this->input->post('Id');
            $this->master_model->master_update($coupon_arr, 'sponsored_company', $where);
            $this->session->set_flashdata('success', 'Coupon Updated Successfully !');
        } else {
            /* save ther data in table the table, if form in add mode */
            $Id = $this->master_model->master_insert($coupon_arr, 'sponsored_company');
            $this->session->set_flashdata('success', 'Coupon Added successfully !');
        }

        /* Updating the coupons categories */
        /* deleting existing assigned categories */
        $this->master_model->master_delete("sponsored_category", " CouponID = " . $Id);
        
        
       
        /* Adding new assigned categories */
        
        if($categories){
            foreach ($categories as $category) {
                $data['CouponID'] = $Id;
                $data['CategoryID'] = $category;
                $this->master_model->master_insert($data, 'sponsored_category');
            }
        }
        redirect('/category/category_sponsored_coupons', 'refresh');
    }
    public function update_category_sequence(){
        
        
        $records = $this->input->post('records');
        $i = 1;
        foreach ($records as $category_id) {
            $order = $i;
            $data['categorySequence'] = $order;
            $where['CategoryID'] = $category_id;
            $this->master_model->master_update($data, 'category', $where);
            $i++;
        }
        
        //generating the xml file
                    $front_site = $this->config->item('front_site');
                    $current_site_name = $this->nativesession->get('site_name');
                    $url = $data['front_site_link'] = $front_site[$current_site_name];
                    $url = $url . "generate_xml";
                    execute_link($url); //common helper
    }
    
    
    /*
     * @Function : update_category_voucher_sequence
     * @Description : Update the sequence ordering of voucher for category
     * @Access : Public
     */
    public function update_category_voucher_sequence(){
        
        
        $records = $this->input->post('recordsArray');
        $category_id = $this->input->post('category_id');
        
         // remove the existing ordering of voucher for current company
        $where = "CategoryID =" . $category_id;
        $data['SequenceNum'] = 21;
        $this->master_model->master_update($data, 'productcategory', $where);
        
        
        $i = 1;
        foreach ($records as $product_id) {
            $where = array();
            $order = $i;
            $data['SequenceNum'] = $i;
            $where['CategoryID'] = $category_id;
            $where['ProductID'] = $product_id;
            $this->master_model->master_update($data, 'productcategory', $where);
            $i++;
        }
        
        //generating the xml file
                    $front_site = $this->config->item('front_site');
                    $current_site_name = $this->nativesession->get('site_name');
                    $url = $data['front_site_link'] = $front_site[$current_site_name];
                    $url = $url . "generate_xml";
                    execute_link($url); //common helper
    }
    
    
    
    
    
    /* order_category_voucher
     * @Function : order_category_vouher()
     * @Description to list order category vouchers
     * @access: public
     */
    
    function category_voucher_sequence(){
        
        ini_set('memory_limit', '-1');
        
        user_auth_access(); //common helper function 
        $config['base_url'] = base_url() . "category/category_voucher_sequence";
        
        $perpage = 10;
        $this->my_pagination->per_page = 10;
        $config = $this->my_pagination->pagination_config($config);

        $tablename = "product";
        $select = "product.*, company.CompanyName, company.CompanyID, company.ContactPerson, category.categorybanner,category.CategoryName,company.primary_cat,productcategory.SequenceNum";
        $search = "";
        /* Search Module */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_value = htmlentities($_GET['search']);
            $search = array(
                'company.CompanyName' => $search_value,
                'product.ProductName' => $search_value,
                'category.CategoryName' => $search_value,
            );
        }
        
        /* Joins Module */
        $join = array(
            'productcategory' => 'product.ProductID = productcategory.ProductID|left',
            'company' => 'company.CompanyID = product.CompanyID|left',
            'category' => 'category.CategoryID = productcategory.CategoryID|left',
           
        );
       
        $category_id = 0;
        /* Advance Filter */
        if ($this->input->get('category_id')) {
            $category_id = $this->input->get('category_id');
        }
        
        
        //getting manual ordered categoy's voucher
        $where ="category.CategoryID = ".$category_id ."  AND product.ProductEndDate >= CURRENT_DATE and productcategory.SequenceNum != 21";
        $data['detail'] = $this->master_model->getList("result_set", $field = 'productcategory.SequenceNum', $order = false, $offset = '', $perpage =false, $tablename, $search, $join, $where, $select, $distinct = TRUE);
        
        
        //getting categry's voucher without manual order
        $where ="category.CategoryID = ".$category_id ."  AND product.ProductEndDate >= CURRENT_DATE and productcategory.SequenceNum = 21";
        $config['total_rows'] = $this->master_model->getList("rows", $field = 'productcategory.SequenceNum', $order = false, $offset = '', $perpage, $tablename, $search, $join, $where, $select, $distinct = TRUE);
        
        $this->my_pagination->initialize($config);
        $data['detail_without_order'] = $this->master_model->getList("result_set", $field = 'productcategory.SequenceNum', $order = false, $offset = '', $perpage, $tablename, $search, $join, $where, $select, $distinct = TRUE);

        $data['total_rows'] = $config['total_rows'];
        
        
        
        
        
        
       
        $data['base_url'] = $config['base_url'];
        $meta_data['page_title'] = "Category Vouchers Ordering";
        if (IS_AJAX) {
            $data['is_ajax'] = '1';
            $this->load->view('admin/category/order_category_voucher', $data);
        } else {
            $this->load->view('admin/head', $meta_data);
            $this->load->view('admin/category/order_category_voucher', $data);
            $this->load->view('admin/footer');
        }
    }
    
    /*
     * @Function : remove_category_voucher
     * @Description : Remove the voucher from category
     * @Access : Public
     * @Return : boolean
     */
    public function remove_category_voucher(){
        $return['is_deleted'] = 0;
        $return['message'] = "Vouchers removed from category successfully !";
        $voucher_ids = $this->input->post('voucher_array');
        $category_id = $this->input->post('category_id');
        
        if ($voucher_ids && count($voucher_ids) > 0) {
            foreach ($voucher_ids as $voucher_id) {
                $this->master_model->master_delete("productcategory", " CategoryID= '" . $category_id . "' AND  ProductID = " . $voucher_id . "");
            }
            $return['is_deleted'] = 1;
        }
        echo json_encode($return);
    }
    
    
    
    
    public function validateCategoryKeywords($field_value){
        
         $result  = $this->master_model->get_master_row("rejected_keyword", $select = "module_name,keywords", $where = " module_name = 'validate_category_keywords'");
         $rejected_keywords         = explode("|",$result["keywords"]);
         $posted_keywords_arr       = explode(",",$field_value);
         $remove_words              = array();
         $status                    = true;
         
         foreach($posted_keywords_arr as $key=>$val){
             
            if(in_array($val, $rejected_keywords)){
                $status = FALSE;
                $remove_words[] = $val;
                unset($posted_keywords_arr[$key]);
            } 
            
         }
         $_POST["keywords"] = implode(",",$posted_keywords_arr);
         return true;
            
         // If want to show error message then uncomment this and remove above return true; linke;
//         if(!$status){   
//            $this->form_validation->set_message('validateCategoryKeyword', "Remove this keywords from above field : ".implode(", ",$remove_words));
//            return false;
//         }
//         return true;
                
        
    }
    
    
    
    
    public function product_by_assigned_reason($keyword,$categoryId){
        
        $where = " assigned_reason LIKE 'Category Keywords -{$keyword}' AND CategoryID = {$categoryId}";
        $join  = array(
            "product"   => " productcategory.ProductID  = product.ProductID|LEFT"
        );

        $select = "productcategory.*,product.ProductName,product.ProductDescription,product.ProductPromotionCode";
        $result = $this->master_model->get_master("productcategory", $where, $join, $order = false, $field = false, $select, $limit= false);
        
        return $result;
    }
    
    
    
    
    /*
     * onUpdate rejected keywords, 
     * Refine category allocation for old vouchers
     */
    public function onUpdate_allocateCategories($categoryId, $postedData, $fetchData){
        
       
       if(!empty($postedData) && !empty($postedData["keywords"])){
           
           // Load model and helper
           $this->load->model('category_model');
           $this->load->helper("import_helper");
           
           
           
           /* Get Rejected Keywords in category keywords:: */
            if(empty($this->rejected_category_keywords) && sizeof($this->rejected_category_keywords)==0){

                $this->rejected_category_keywords = $this->master_model->get_master_row("rejected_keyword", $select = "module_name,keywords", $where = " module_name = 'validate_category_keywords'");
            }
                
            
            /* Get Categories :: */
            if(empty($this->category_arr) && sizeof($this->category_arr)==0){

                $category_result = $this->category_model->getParentChildCategories("AND keywords <>'' && keywords IS NOT NULL");
                foreach($category_result as $key => $val){
                    
                    if(!empty($val["keywords"])){
                        $this->category_arr[$val["CategoryID"]] = array_map('trim', explode(",",$val["keywords"]));
                    }    
                }
            }
            
            
           // Trim and lowercase each keyword 
           $old_keywords    = array_map(function($item){
               return strtolower(trim($item));
               
           },explode(",",$fetchData["keywords"]));
           
           
           // Trim and lowercase each keyword
           $posted_keywords = array_map(function($item){
               return strtolower(trim($item));
               
           },explode(",",$postedData["keywords"]));
           
           
           
           
           
           
           
                
             
            
            /* -------------------------------------------------------------------------------------
             * [ Removed keyword ] If Removed keyword then, find products with this keywords & category 
             * and allocate new category using import_helper
             * -------------------------------------------------------------------------------------
             */
           // Find Removed element 
           $removed_array = array_diff($old_keywords,$posted_keywords);
           
           if(!empty($removed_array)){
                
                foreach($removed_array as $key=>$val){
                    
                    //get products 
                    $result = $this->product_by_assigned_reason($val,$categoryId);


                    //If Products found then remove this category and allocate new categories to this 
                    //vouchers 
                    if(!empty($result)){
                        
                        
                        // Remove product(voucher) category from productcategory table
                        $remove_ids = array_map(function($element){
                           return $element["ID"]; 
                        }, $result);
                        $remove_ids = implode(",",$remove_ids);
                        
                        if(!empty($remove_ids))
                            $this->master_model->master_delete("productcategory", " ID IN ({$remove_ids})");
                        
                        
                        
                        foreach($result as $key=>$val){ 

                           $product_id = $val["ProductID"];

                             // Assign categories based on Product/Voucher Name, Description, Code
                           
                            // Product Name
                            $flag = allocateCategories($product_id, $val["ProductName"], $this->category_arr,$this->rejected_category_keywords);

                            if (!$flag) {
                                // Description
                                $flag = allocateCategories($product_id, $val["ProductDescription"], $this->category_arr,$this->rejected_category_keywords);
                                
                                if (!$flag) {
                                    
                                    //Promotion Code
                                    $flag = allocateCategories($product_id, $val["ProductPromotionCode"], $this->category_arr ,$this->rejected_category_keywords);
                                }
                            }
                            // ---


                        }

                    }

                }
           
           }
           

            // [New Keyword Added] 
            // If newly added then what to do
            // Note :: find the company of this category and find vouchers of that company and apply the allocate category rules
           
            $added_array    = array_diff($posted_keywords,$old_keywords);
            
            if(!empty($added_array)){
                
                // Find company id
                $select = "*";
                $where  = " CategoryID = ".$categoryId;  
             
                $companycategory_result = $this->master_model->get_master("companycategory", $where, $join=FALSE, $order = "RANDOM", $field = "CompanyID", $select, $limit= false);
                
                
                // If there is a company of this category then allocate categories
                if(!empty($companycategory_result)){
                    
                    // Loop through all company voucher if found then break
                    foreach($companycategory_result as $key=>$val){
                        
                         $where = " CompanyID = {$val["CompanyID"]} && ProductEndDate >= CURDATE()";


                        $select = "product.ProductID, product.ProductName,product.ProductDescription,product.ProductPromotionCode,product.ProductEndDate";
                        $product_result = $this->master_model->get_master("product", $where, $join, $order = false, $field = false, $select, $limit= false);
                        

                        if(!empty($product_result)){    
                            
                                foreach($product_result as $key=>$val){ 

                                    $product_id = $val["ProductID"];

                                     // Assign categories based on Product/Voucher Name, Description, Code

                                            // Product Name
                                            $flag = allocateCategories($product_id, $val["ProductName"], $this->category_arr,$this->rejected_category_keywords);

                                            if (!$flag) {
                                                // Description
                                                $flag = allocateCategories($product_id, $val["ProductDescription"], $this->category_arr,$this->rejected_category_keywords);

                                                if (!$flag) {

                                                    //Promotion Code
                                                    $flag = allocateCategories($product_id, $val["ProductPromotionCode"], $this->category_arr ,$this->rejected_category_keywords);
                                                }
                                            }
                                            // ---

                                }
                                
                              // If there is any categories assigned product result match found then break loop  
                             if($flag) break;
                        }
                        
                    
                    }
                   
                
             } 
                
            }
           
       } 
        
        
    }
    
    

}
