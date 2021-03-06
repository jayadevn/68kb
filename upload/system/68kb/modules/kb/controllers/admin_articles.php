<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 68kb
 *
 * An open source knowledge base script
 *
 * @package		68kb
 * @author		Eric Barnes (http://ericlbarnes.com)
 * @copyright	Copyright (c) 2010, 68kb
 * @license		http://68kb.com/user_guide/license.html
 * @link		http://68kb.com
 * @since		Version 2.0
 */

// ------------------------------------------------------------------------

/**
 * Admin Users Controller
 *
 * @subpackage	Controllers
 * @link		http://68kb.com/user_guide/admin/users.html
 *
 */
class Admin_articles extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('users_model');
		$this->load->model('articles_model');

		if ( ! $this->users_auth->check_role('can_manage_users'))
		{
			show_error(lang('not_authorized'));
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Show table grid
	*/
	public function index()
	{
		$data['nav'] = 'articles';

		$this->template->set_metadata('stylesheet', base_url() . 'themes/cp/css/smoothness/jquery-ui.css', 'link');
		$this->template->set_metadata('js', 'js/dataTables.min.js', 'js_include');

		$this->template->title = lang('lang_manage_users');

		$this->template->build('admin/articles/grid', $data);
	}

	// ------------------------------------------------------------------------

	/**
	* add article
	*
	*/
	public function add()
	{
		$data['nav'] = 'articles';

		$this->template->title(lang('lang_add_article'));

		// Get the categories
		$this->load->library('categories/categories_library');
		$this->load->model('categories/categories_model');
		$data['tree'] = $this->categories_model->walk_categories(0, 0, 'checkbox', 0, '', TRUE);

		$data['action'] = 'add';

		$this->load->helper(array('form', 'url', 'html'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('article_title', 'lang:lang_title', 'required');
		$this->form_validation->set_rules('article_uri', 'lang:lang_uri', 'alpha_dash');
		$this->form_validation->set_rules('article_keywords', 'lang:lang_keywords', 'trim|xss_clean');
		$this->form_validation->set_rules('article_short_desc', 'lang:lang_short_description', 'trim|xss_clean');
		$this->form_validation->set_rules('article_description', 'lang:lang_description', 'trim|xss_clean');
		$this->form_validation->set_rules('article_display', 'lang:lang_display', 'trim');
		$this->form_validation->set_rules('article_order', 'lang:lang_weight', 'numeric');
		$this->events->trigger('articles/validation');

		if ($this->form_validation->run() == FALSE)
		{
			$this->template->build('admin/articles/form', $data);
		}
		else
		{
			$data = array(
				'article_author' => (int) $this->session->userdata('user_id'),
				'article_title' => $this->input->post('article_title', TRUE),
				'article_keywords' => $this->input->post('article_keywords', TRUE),
				'article_short_desc' => $this->input->post('article_short_desc', TRUE),
				'article_description' => $this->input->post('article_description', TRUE),
				'article_display' => $this->input->post('article_display', TRUE),
				'article_order' => $this->input->post('article_order', TRUE)
			);

			$id = $this->articles_model->add_article($data);

			// Insert any fields
			$fields = array('article_field_id' => $id);
			//$fields_data = array_merge($fields, $fields_array);
			$this->articles_model->add_fields($fields);

			$this->session->set_flashdata('msg', lang('lang_settings_saved'));

			if (is_int($id))
			{
				// now add cat to product relationship
				if (isset($_POST['cats']))
				{
					$this->articles_model->insert_cats($_POST['cats'], $id);
				}

				if ($_FILES['userfile']['name'] != "")
				{
					$target = ROOTPATH .'uploads/'.$id;
					$this->_mkdir($target);
					$config['upload_path'] = $target;
					$config['allowed_types'] = $this->config->item('allowed_types');
					$this->load->library('upload', $config);
					if ( ! $this->upload->do_upload())
					{
						$this->session->set_flashdata('error', $this->upload->display_errors());
						redirect('admin/kb/articles/edit/'.$id);
					}
					else
					{
						$upload = array('upload_data' => $this->upload->data());
						$insert = array(
							'article_id' => $id,
							'attach_title' => $this->input->post('attach_title', TRUE),
							'attach_file' => $upload['upload_data']['file_name'],
							'attach_type' => $upload['upload_data']['file_type'],
							'attach_size' => $upload['upload_data']['file_size']
						);
						$this->db->insert('attachments', $insert);
						$data['attach'] = $this->articles_model->get_attachments($id);
					}
				}
			    if (isset($_POST['save']) && $_POST['save']<>"")
			    {
			    	redirect('admin/kb/articles/edit/'.$id);
			    }
			    else
			    {
			    	redirect('admin/kb/articles/');
			    }
			}
			redirect('admin/kb/articles/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* add article
	*
	*/
	public function edit($id = '')
	{
		if ($id == '' OR ! is_numeric($id))
		{
			redirect('admin/kb/articles');
		}

		$id = (int) $id;

		$data['nav'] = 'articles';

		$this->template->title(lang('lang_edit_article'));

		// Get the categories
		$this->load->library('categories/categories_library');
		$this->load->model('categories/categories_model');
		$data['tree'] = $this->categories_model->walk_categories(0, 0, 'checkbox', 0, '', TRUE);
		$data['row'] = $this->articles_model->get_article($id);
		$data['attach'] = $this->articles_model->get_attachments($id);
		$data['cats'] = $this->articles_model->get_category_relationship($id);
		$data['tree'] = $this->categories_model->walk_categories(0, 0, 'checkbox', 0, $data['cats'], TRUE);

		if($data['row']['article_author'] > 0)
		{
			$user = $this->users_model->get_user($data['row']['article_author']);
			$data['username'] = $user['user_username'];
		}

		$data['action'] = 'edit';

		$this->load->helper(array('form', 'url', 'html'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('article_title', 'lang:lang_title', 'required');
		$this->form_validation->set_rules('article_uri', 'lang:lang_uri', 'alpha_dash');
		$this->form_validation->set_rules('article_keywords', 'lang:lang_keywords', 'trim|xss_clean');
		$this->form_validation->set_rules('article_short_desc', 'lang:lang_short_description', 'trim|xss_clean');
		$this->form_validation->set_rules('article_description', 'lang:lang_description', 'trim|xss_clean');
		$this->form_validation->set_rules('article_display', 'lang:lang_display', 'trim');
		$this->form_validation->set_rules('article_order', 'lang:lang_weight', 'numeric');
		$this->events->trigger('articles/validation');

		if ($this->form_validation->run() == FALSE)
		{
			$this->template->build('admin/articles/form', $data);
		}
		else
		{
			$owner = 1;
			if ($user = $this->users_model->get_user($this->input->post('article_author', TRUE)))
			{
				$owner = $user['user_id'];
			}

			$data = array(
				'article_uri' => $this->input->post('article_uri', TRUE),
				'article_author' => $owner,
				'article_title' => $this->input->post('article_title', TRUE),
				'article_keywords' => $this->input->post('article_keywords', TRUE),
				'article_short_desc' => $this->input->post('article_short_desc', TRUE),
				'article_description' => $this->input->post('article_description', TRUE),
				'article_display' => $this->input->post('article_display', TRUE),
				'article_order' => $this->input->post('article_order', TRUE)
			);

			$this->articles_model->edit_article($id, $data);

			$this->session->set_flashdata('msg', lang('lang_settings_saved'));

			// now add cat to product relationship
			if (isset($_POST['cats']))
			{
				$this->articles_model->insert_cats($_POST['cats'], $id);
			}

			if ($_FILES['userfile']['name'] != "")
			{
				$target = ROOTPATH .'uploads/'.$id;
				$this->_mkdir($target);
				$config['upload_path'] = $target;
				$config['allowed_types'] = $this->config->item('allowed_types');
				$this->load->library('upload', $config);
				if ( ! $this->upload->do_upload())
				{
					$this->session->set_flashdata('error', $this->upload->display_errors());
					redirect('admin/kb/articles/edit/'.$id);
				}
				else
				{
					$upload = array('upload_data' => $this->upload->data());
					$insert = array(
						'article_id' => $id,
						'attach_title' => $this->input->post('attach_title', TRUE),
						'attach_file' => $upload['upload_data']['file_name'],
						'attach_type' => $upload['upload_data']['file_type'],
						'attach_size' => $upload['upload_data']['file_size']
					);
					$this->db->insert('attachments', $insert);
					$data['attach'] = $this->articles_model->get_attachments($id);
				}
			}

			if (isset($_POST['save']) && $_POST['save']<>"")
		    {
		    	redirect('admin/kb/articles/edit/'.$id);
		    }

			redirect('admin/kb/articles/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Delete an Uploaded file.
	*
	*/
	public function upload_delete($id = '')
	{
		$this->load->helper('file');
		if ( ! is_numeric($id))
		{
			redirect('admin/kb/articles/');
		}
		$id = (int) $id;

		$this->db->select('attach_id, article_id, attach_file')->from('attachments')->where('attach_id', $id);
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$article_id = $row->article_id;
			unlink(ROOTPATH .'uploads/'.$row->article_id.'/'.$row->attach_file);
			$this->db->delete('attachments', array('attach_id' => $id));
			redirect('admin/kb/articles/edit/'.$article_id.'/#attachments');
		}
		else
		{
			redirect('admin/kb/articles/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Attempt to make a directory to house uploaded files.
	*
	* @access	private
	*/
	private function _mkdir($target)
	{
		// from php.net/mkdir user contributed notes
		if (file_exists($target))
		{
			if ( ! @is_dir($target))
			{
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}

		// Attempting to create the directory may clutter up our display.
		if (@mkdir($target))
		{
			$stat = @stat(dirname($target));
			$dir_perms = $stat['mode'] & 0007777;  // Get the permission bits.
			@chmod($target, $dir_perms);
			return TRUE;
		}
		else
		{
			if (is_dir(dirname($target)))
			{
				return FALSE;
			}
		}

		// If the above failed, attempt to create the parent node, then try again.
		if ($this->_mkdir(dirname($target)))
		{
			return $this->_mkdir($target);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	* Grid
	*
	* This is used by the data table js.
	*
	* @access	public
	* @return	string
	*/
	public function grid()
	{
		$iTotal = $this->db->count_all('articles');

		$this->db->start_cache();

		//$this->db->select('user_id, user_ip, user_first_name, user_last_name, user_email, user_username, user_group, user_join_date, user_last_login');
		$this->db->from('articles');

		// User Level
		if ($this->session->userdata('user_group') == 4)
		{
			$this->db->where('article_author', $this->session->userdata['userid']);
		}

		/* Searching */
		if($this->input->post('sSearch') != '')
		{
			$q = $this->input->post('sSearch', TRUE);
			$this->db->orlike('article_title', $q);
			$this->db->orlike('article_short_desc', $q);
			$this->db->orlike('article_description', $q);
			$this->db->orlike('article_uri', $q);
		}

		/* Sorting */
		if ($this->input->post('iSortCol_0'))
		{
			$sort_col = $this->input->post('iSortCol_0');
			for($i=0; $i < $sort_col; $i++)
			{
				$this->db->order_by($this->_column_to_field($this->input->post('iSortCol_'.$i)), $this->input->post('iSortDir_'.$i));
			}
		}
		else
		{
			$this->db->order_by('article_modified', 'desc');
		}

		$this->db->stop_cache();

		$iFilteredTotal = $this->db->count_all_results();

		$this->db->start_cache();

		/* Limit */
		if ($this->input->post('iDisplayStart') && $this->input->post('iDisplayLength') != '-1' )
		{
			$this->db->limit($this->input->post('iDisplayLength'), $this->input->post('iDisplayStart'));
		}
		elseif($this->input->post('iDisplayLength'))
		{
			$this->db->limit($this->input->post('iDisplayLength'));
		}

		$query = $this->db->get();

		$output = '{';
		$output .= '"sEcho": '.$this->input->post('sEcho').', ';
		$output .= '"iTotalRecords": '.$iTotal.', ';
		$output .= '"iTotalDisplayRecords": '.$iFilteredTotal.', ';
		$output .= '"aaData": [ ';

		foreach ($query->result_array() as $row)
		{
			$cat = '';

			// Here we are flushing cache because of the "get_cats" query.
			$this->db->flush_cache();

			$cats = $this->articles_model->get_cats_by_article($row['article_id']);
			foreach($cats->result_array() as $item)
			{
				$cat .= anchor('admin/categories/edit/'.$item['cat_id'], $item['cat_name']).', ';
			}

			$status = '<span class="not_active">'.lang('lang_not_active').'</span>';
			if ($row['article_display'] == 'y')
			{
				$status = '<span class="active">'.lang('lang_active').'</span>';
			}

			$title = anchor('admin/kb/articles/edit/'.$row['article_id'], $row['article_title']);
			$output .= "[";
			$output .= '"'.addslashes($title).'",';
			$output .= '"'.addslashes(reduce_multiples($cat, ", ", TRUE)).'",';
			$output .= '"'.addslashes(date($this->config->item('short_date_format'), $row['article_date'])).'",';
			$output .= '"'.addslashes(date($this->config->item('short_date_format'), $row['article_modified'])).'",';
			$output .= '"'.addslashes($status).'",';
			$output .= '"<input type=\"checkbox\" name=\"article_id[]\" value=\"'.$row['article_id'].'\" />"';
			$output .= "],";
		}

		$output = substr_replace( $output, "", -1 );
		$output .= '] }';

		echo $output;
	}

	// ------------------------------------------------------------------------

	/**
	* Relate column to field
	*
	* This is used by the data table js.
	*
	* @param	string
	* @return	string
	*/
	private function _column_to_field($i)
	{
		if ($i == 0)
		{
			return "article_title";
		}
		elseif ($i == 1)
		{
			return "article_title";
		}
		elseif ($i == 2)
		{
			return "article_date";
		}
		elseif ($i == 3)
		{
			return 'article_modified';
		}
		elseif ($i == 5)
		{
			return 'article_display';
		}
		elseif ($i == 6)
		{
			return "listing_price";
		}
	}
}
/* End of file admin_articles.php */
/* Location: ./upload/includes/68kb/modules/kb/controllers/admin_articles.php */