<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_model {

	public function get_merchant_home($cpid, $upid) {
		if($upid != '' && $upid != '0') {
			$this->db->select('a.campaign_pid, a.company_pid, a.campaign_image, a.campaign_question, a.campaign_option1, a.campaign_option2, a.campaign_option3, a.campaign_option4, b.answer_pid, c.company_address, c.company_logo');
		} else {
			$this->db->select('a.campaign_pid, a.company_pid, a.campaign_image, c.company_address, c.company_logo');
		}
		
		$this->db->from('campaign a');
		
		if($upid != '') {
			$this->db->join('answer b', "a.campaign_pid = b.campaign_pid AND b.user_pid = $upid", 'left');
			$this->db->join('data_user d', "d.user_pid = $upid AND a.target_city = d.city_pid", 'left');
		}
		
		$this->db->join('data_company c', 'a.company_pid = c.company_pid', 'left');
		$this->db->where('a.company_pid', $cpid);
		$this->db->where('a.campaign_type', '1');
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_merchant_home_v2($cpid) {
		$this->db->select('company_pid, company_logo');
		$this->db->from('data_company');
		$this->db->where('company_PID', $cpid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_merchant_ads($cpid) {
		$this->db->select('campaign_pid, campaign_title, campaign_image, company_pid, campaign_point');
		
		$this->db->from('campaign');
		$this->db->where('company_pid', $cpid);
		$query = $this->db->get();
		$result = $query->result();
		if($result) {
			return $result;
		} else {
			return '';
		}
	}
	
	public function get_merchant_follower($cpid) {
		$this->db->select("COUNT(follower_pid) as follower");
		$this->db->from('follower');
		$this->db->where('company_PID', $cpid);
		$query = $this->db->get();
		$result = $query->result();
		if($result) {
			return $result[0] -> follower;
		} else {
			return '0';
		}
	}

	public function get_promo_pages($companyPid) {
		$this->db->select('category_pid, category_name');
		$this->db->from('promo_category');
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result_array();

		$arr_category = array();
		if($result) {
			foreach($result as &$c) {
				// Get preview images
				$this->db->select('company_pid, campaign_pid, campaign_image, campaign_point');
				$this->db->from('campaign');
				$this->db->where('category_pid', $c['category_pid']);
				$this->db->limit('10');
				$query_campaign = $this->db->get();
				$result_campaign = $query_campaign->result_array();

				if($result_campaign) {
					$c['campaign'] = $result_campaign;
				}
			}
		}

		return $result;

		/*
		echo '<div style="display: inline-block; vertical-align: top; height: 600px; overflow: auto;">';
		echo '<pre>';
		print_r($result);
		echo '</pre>';
		echo '</div>';
		*/
	}

	public function get_promotions($categoryPid, $page) {
		$offset = $page * 7;

		// Get page rows
		$this->db->from('page_row');
		$this->db->where('promo_category_pid', $categoryPid);
		$this->db->order_by('page_row_index', 'ASC');
		$this->db->limit('7', $offset);
		$query_row = $this->db->get();
		$result_row = $query_row->result_array();

		// Get page grids
		if($result_row) {
			$arr_row = array();

			foreach($result_row as $r) {
				array_push($arr_row, $r['page_row_pid']);
			}

			$arr_field = implode(',', $arr_row);

			$this->db->select('
				a.page_row_pid,
				a.page_grid_pid,
				a.page_grid_index,
				a.page_grid_col,
				a.page_grid_x,
				a.page_grid_y,
				a.campaign_pid,
				b.campaign_image,
				b.company_pid,
				b.campaign_point,
			');
			$this->db->from('page_grid a');
			$this->db->join('campaign b', 'a.campaign_pid = b.campaign_pid', 'left');
			$this->db->where_in('a.page_row_pid', $arr_row);
			$this->db->order_by("FIELD(a.page_row_pid, $arr_field), a.page_grid_index ASC, a.page_grid_pid ASC");
			$query_grid = $this->db->get();
			$result_grid = $query_grid->result();

			// Make grid array
			if($result_grid) {

				// Make array
				$ppid = '';
				$i = 0;
				$array = $row = $prow = $urow = $crow = $arr_col = $arr_index = array();
	
				foreach($result_grid as $r) {
	
					if($i == 0) {
						$ppid = $r -> page_row_pid;
						$prindex = '1';
					}
	
					$pid = $r -> page_row_pid;
					$rindex = $r -> page_grid_index;

					if($pid == $ppid) {

						if($prindex != $rindex) {
							$crow['width'] = $prow[0]['page_grid_col'];
							$crow['campaign'] = $prow;
							array_push($arr_col, $crow);
							$prow = $crow = array();
						}

						$row['page_row_pid'] = $r -> page_row_pid;
	
						$urow['page_grid_pid'] = $r -> page_grid_pid;
						$urow['page_grid_index'] = $r -> page_grid_index;
						$urow['page_grid_col'] = $r -> page_grid_col;
						$urow['page_grid_x'] = $r -> page_grid_x;
						$urow['page_grid_y'] = $r -> page_grid_y;
						$urow['campaign_pid'] = $r -> campaign_pid;
						$urow['company_pid'] = $r -> company_pid;
						$urow['campaign_image'] = $r -> campaign_image;
						$urow['campaign_point'] = $r -> campaign_point;

						array_push($prow, $urow);
						//echo '<pre>'; print_r($arr_col); echo '</pre>';
	
						if($i+1 == count($result_grid)) {
							$crow['width'] = $prow[0]['page_grid_col'];
							$crow['campaign'] = $prow;
							array_push($arr_col, $crow);
							$row['grid'] = $arr_col;
							array_push($array, $row);
							//echo '<pre>'; print_r($arr_col); echo '</pre>';
						}

						$prindex = $r -> page_grid_index;
					} else {
						$prindex = '0';

						if($prindex != $rindex) {
							$crow['width'] = $prow[0]['page_grid_col'];
							$crow['campaign'] = $prow;
							array_push($arr_col, $crow);
							$prow = $crow = array();
						}

						//echo '<pre>'; print_r($arr_col); echo '</pre>';
						$row['grid'] = $arr_col;
						array_push($array, $row);
	
						$row = array();
						$arr_col = array();
	
						$row['page_row_pid'] = $r -> page_row_pid;
	
						$urow['page_grid_pid'] = $r -> page_grid_pid;
						$urow['page_grid_index'] = $r -> page_grid_index;
						$urow['page_grid_col'] = $r -> page_grid_col;
						$urow['page_grid_x'] = $r -> page_grid_x;
						$urow['page_grid_y'] = $r -> page_grid_y;
						$urow['campaign_pid'] = $r -> campaign_pid;
						$urow['company_pid'] = $r -> company_pid;
						$urow['campaign_image'] = $r -> campaign_image;
						$urow['campaign_point'] = $r -> campaign_point;
	
						array_push($prow, $urow);
						//array_push($arr_col, $prow);

						//$prow = array();
	
						if($i+1 == count($result_grid)) {
							$crow['width'] = $prow[0]['page_grid_col'];
							$crow['campaign'] = $prow;
							array_push($arr_col, $crow);
							$row['grid'] = $arr_col;
							array_push($array, $row);
						}

						$prindex = $r -> page_grid_index;
					}
	
					$ppid = $r -> page_row_pid;
					$i++;
				}

				// Combine row & grid
				$x = 0;
				foreach($result_row as &$row) {
					$page_row_pid = $row['page_row_pid'];

					if(array_key_exists($x, $array) && $page_row_pid == $array[$x]['page_row_pid']) {
						$row['grid'] = $array[$x]['grid'];
						$x++;
					} else {
						$row['grid'] = array();
					}
				}
				
				return $result_row;
			} else {
				return $result_row;	
			}
		}

		/*
		echo '<div style="display: inline-block; vertical-align: top; height: 600px; overflow: auto;">';
		echo '<pre>';
		print_r($result_row);
		echo '</pre>';
		echo '</div>';
		
		echo '<div style="display: inline-block; vertical-align: top; height: 600px; overflow: auto;">';
		echo '<pre>';
		print_r($result_grid);
		echo '</pre>';
		echo '</div>';

		echo '<div style="display: inline-block; vertical-align: top; height: 600px; overflow: auto;">';
		echo '<pre>';
		print_r($array);
		echo '</pre>';
		echo '</div>';
		*/
	}
	
	public function get_company_detail($cpid) {
		$this->db->select('company_address');
		$this->db->from('data_company');
		$this->db->where('company_pid', $cpid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_user_point($userPid) {
		$this->db->select('user_point');
		$this->db->from('data_user');
		$this->db->where('user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		if($result) {
			$user_point = $result[0] -> user_point;
			return $user_point;
		} else {
			return '';
		}
	}
	
	public function get_ads($userPid) {
		$this->db->select('
			a.campaign_pid, a.campaign_image,
			b.company_pid, b.company_name
		');
		
		$this->db->from('campaign a');
		$this->db->join('data_company b', 'a.company_pid = b.company_pid', 'left');
		
		if($userPid > 0) {
			$this->db->join('answer c', "a.campaign_pid = c.campaign_pid AND c.user_pid = '$userPid'", 'left');
			$this->db->where('c.answer_pid IS NULL', null, false);
		}
		
		//$this->db->where('a.campaign_point >', '0');
		
		$this->db->group_by('a.company_pid');
		$this->db->order_by('a.company_pid', 'DESC');
		$query = $this->db->get();
		$result = $query->result();
		if($result) {
			return $result;
		} else {
			return '';
		}
	}
	public function get_feeds() {
		$this->db->select('
			a.campaign_pid, a.campaign_image, a.campaign_created, a.campaign_title, a.campaign_point, 
			b.company_pid, b.company_name
		');
		
		$this->db->from('campaign a');
		$this->db->join('data_company b', 'a.company_pid = b.company_pid', 'left');
		$this->db->order_by('a.campaign_pid', 'DESC');
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_logs($userPid) {
		$this->db->select('
			a.answer_pid, a.campaign_pid, a.answer_datetime, a.answer_correct,
			b.campaign_point, b.campaign_title
		');
		
		$this->db->from('answer a');
		$this->db->join('campaign b', 'a.campaign_pid = b.campaign_pid', 'left');
		$this->db->where('a.user_pid', $userPid);
		$this->db->order_by('a.answer_pid', 'DESC');
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_inbox() {
		$this->db->select('notification_pid, notification_datetime, notification_title');
		$this->db->from('notification');
		$query = $this->db->get();
		$result = $query->result();
		if($result) {
			return $result;
		} else {
			$empty = [];
			return $empty;
		}
	}
	
	public function get_stats_answered($userPid) {
		$this->db->select('
			COUNT(a.answer_pid) as count_answer, 
		');
		$this->db->from('answer a');
		$this->db->where('user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		$count_answer = 0;
		if($result) {
			$count_answer = $result[0] -> count_answer;
		}
		return $count_answer;
	}
	
	public function get_stats_earned($userPid) {
		$this->db->select('campaign_pid');
		$this->db->from('answer');
		$this->db->where('user_pid', $userPid);
		$this->db->where('answer_correct', '1');
		$query_answer = $this->db->get();
		$result_answer = $query_answer->result_array();
		
		if($result_answer) {
			$arr_cpid = array();
			foreach($result_answer as $a) {
				array_push($arr_cpid, $a['campaign_pid']);
			}
			
			// Total earned
			$this->db->select('SUM(campaign_point) as total_earned');
			$this->db->from('campaign');
			$this->db->where_in('campaign_pid', $arr_cpid);
			$query = $this->db->get();
			$result = $query->result();
			if($result) {
				$result_earned = $result[0] -> total_earned;
			} else {
				$result_earned = 1;
			}
		} else {
			$result_earned = 0;
		}
		
		return($result_earned);
	}
	
	public function get_stats_favorited($userPid) {
		//$this->db->select('COALESCE(SUM(follower_pid), 0) as total_favorited');
		$this->db->select('COUNT(follower_pid) as total_favorited');
		$this->db->from('follower');
		$this->db->where('user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		if($result && $query->num_rows() > 0) {
			$total_favorited = $result[0] -> total_favorited;
		} else {
			$total_favorited = 0;
		}
		return $total_favorited;
	}
	
	public function get_home_inbox_detail($notifPid) {
		$this->db->from('notification');
		$this->db->where('notification_pid', $notifPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_merchant_tabs($companyPid) {
		$this->db->from('promo_category');
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_campaign_views($campaignPid, $userPid) {
		date_default_timezone_set('Asia/Jakarta');
		$datetime = date('Y-m-d H:i:s');
		
		// Add impression
		$data = array(
			'campaign_pid' => $campaignPid,
			'impression_datetime' => $datetime,
			'user_pid' => $userPid
		);
		$this->db->insert('impression', $data);
		
		// Get impression
		$this->db->select('COUNT(impression_pid) as impression');
		$this->db->from('impression');
		$this->db->where('campaign_pid', $campaignPid);
		$query = $this->db->get();
		$result = $query->result();
		$impression = $result[0] -> impression;
		return $impression;
	}

	public function get_campaign_like($campaignPid) {
		$this->db->select('COUNT(user_like_pid) as adslike');
		$this->db->from('user_like');
		$this->db->where('campaign_pid', $campaignPid);
		$query = $this->db->get();
		$result = $query->result();
		$adslike = $result[0] -> adslike;
		return $adslike;
	}

	public function get_like_status($campaignPid, $userPid) {
		$this->db->select('user_like_pid');
		$this->db->from('user_like');
		$this->db->where('campaign_pid', $campaignPid);
		$this->db->where('user_pid', $userPid);
		$query = $this->db->get();
		if($query->num_rows() > 0) {
			$result = '1';
		} else {
			$result = '0';
		}
		return $result;
	}
	
	public function get_user_companies($userPid) {
		$this->db->select('b.company_pid, b.company_name');
		$this->db->from('user_company a');
		$this->db->join('data_company b', 'a.company_pid = b.company_pid', 'left');
		$this->db->where('a.user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function get_user_company_detail($companyPid) {
		$this->db->select('company_pid, company_name, company_fb, company_ig, company_twitter, company_telephone, company_web, company_email, company_address, company_logo');
		$this->db->from('data_company');
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
	public function register_company($data, $userPid) {
		if($this->db->insert('data_company', $data)) {
			$companyPid = $this->db->insert_id();
			$data_user_company = array(
				'user_pid' => $userPid,
				'company_pid' => $companyPid
			);
			
			if($this->db->insert('user_company', $data_user_company)) {
				return $companyPid;
			} else {
				return '0';
			}
		}
	}
	
	public function get_company_follower($companyPid) {
		$this->db->select('COUNT(follower_pid) as follower');
		$this->db->from('follower');
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result();
		$follower = $result[0] -> follower;
		return $follower;
	}
	
	public function get_company_active_ads($companyPid) {
		date_default_timezone_set('Asia/Jakarta');
		$date = date('Y-m-d');
		
		$this->db->select('COUNT(campaign_pid) as ads');
		$this->db->from('campaign');
		$this->db->where('campaign_date_to >=', $date);
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result();
		$ads = $result[0] -> ads;
		return $ads;
	}

	public function get_campaign_list($companyPid) {
		$this->db->select('company_pid, campaign_pid, campaign_image');
		$this->db->from('campaign');
		$this->db->where('company_pid', $companyPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}

	public function get_user_liked_ads($userPid) {
		$this->db->select('a.company_pid, a.campaign_pid, a.campaign_image');
		$this->db->from('campaign a');
		$this->db->join('user_like b', 'a.campaign_pid = b.campaign_pid', 'left');
		$this->db->where('b.user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}

	public function get_user_favorites($userPid) {
		$this->db->select('a.company_name, a.company_pid, a.company_logo, a.company_address');
		$this->db->from('data_company a');
		$this->db->join('follower b', 'a.company_pid = b.company_pid', 'left');
		$this->db->where('b.user_pid', $userPid);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}
	
}

?>