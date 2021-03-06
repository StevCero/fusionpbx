<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2016-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * xml_cdr class provides methods for adding cdr records to the database
 *
 * @method boolean add
 */
if (!class_exists('xml_cdr')) {
	class xml_cdr {

		/**
		 * define variables
		 */
		public $db;
		public $array;
		public $debug;
		public $fields;

		/**
		 * user summary
		 */
		public $domain_uuid;
		public $quick_select;
		public $start_stamp_begin;
		public $start_stamp_end;
		public $include_internal;
		public $extensions;

		/**
		 * delete method
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}

			//assign private variables (for delete method)
				$this->app_name = 'xml_cdr';
				$this->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
				$this->permission_prefix = 'xml_cdr_';
				$this->list_page = 'xml_cdr.php';
				$this->table = 'xml_cdr';
				$this->uuid_prefix = 'xml_cdr_';
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			if (isset($this)) foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * cdr process logging
		 */
		public function log($message) {
			//save to file system (alternative to a syslog server)
				$fp = fopen($_SESSION['server']['temp']['dir'].'/xml_cdr.log', 'a+');
				if (!$fp) {
					return;
				}
				fwrite($fp, $message);
				fclose($fp);
		}

		/**
		 * cdr fields in the database schema
		 */
		public function fields() {

			$this->fields[] = "xml_cdr_uuid";
			$this->fields[] = "domain_uuid";
			$this->fields[] = "extension_uuid";
			$this->fields[] = "domain_name";
			$this->fields[] = "accountcode";
			$this->fields[] = "direction";
			$this->fields[] = "default_language";
			$this->fields[] = "context";
			$this->fields[] = "xml";
			$this->fields[] = "json";
			$this->fields[] = "missed_call";
			$this->fields[] = "caller_id_name";
			$this->fields[] = "caller_id_number";
			$this->fields[] = "caller_destination";
			$this->fields[] = "destination_number";
			$this->fields[] = "source_number";
			$this->fields[] = "start_epoch";
			$this->fields[] = "start_stamp";
			$this->fields[] = "answer_stamp";
			$this->fields[] = "answer_epoch";
			$this->fields[] = "end_epoch";
			$this->fields[] = "end_stamp";
			$this->fields[] = "duration";
			$this->fields[] = "mduration";
			$this->fields[] = "billsec";
			$this->fields[] = "billmsec";
			$this->fields[] = "bridge_uuid";
			$this->fields[] = "read_codec";
			$this->fields[] = "read_rate";
			$this->fields[] = "write_codec";
			$this->fields[] = "write_rate";
			$this->fields[] = "remote_media_ip";
			$this->fields[] = "network_addr";
			$this->fields[] = "record_path";
			$this->fields[] = "record_name";
			$this->fields[] = "leg";
			$this->fields[] = "pdd_ms";
			$this->fields[] = "rtp_audio_in_mos";
			$this->fields[] = "last_app";
			$this->fields[] = "last_arg";
			$this->fields[] = "cc_side";
			$this->fields[] = "cc_member_uuid";
			$this->fields[] = "cc_queue_joined_epoch";
			$this->fields[] = "cc_queue";
			$this->fields[] = "cc_member_session_uuid";
			$this->fields[] = "cc_agent_uuid";
			$this->fields[] = "cc_agent";
			$this->fields[] = "cc_agent_type";
			$this->fields[] = "cc_agent_bridged";
			$this->fields[] = "cc_queue_answered_epoch";
			$this->fields[] = "cc_queue_terminated_epoch";
			$this->fields[] = "cc_cause";
			$this->fields[] = "waitsec";
			$this->fields[] = "conference_name";
			$this->fields[] = "conference_uuid";
			$this->fields[] = "conference_member_id";
			$this->fields[] = "digits_dialed";
			$this->fields[] = "pin_number";
			$this->fields[] = "hangup_cause";
			$this->fields[] = "hangup_cause_q850";
			$this->fields[] = "sip_hangup_disposition";
			if (is_array($_SESSION['cdr']['field'])) {
				foreach ($_SESSION['cdr']['field'] as $field) {
					$this->fields[] = $field;
				}
			}
		}

		/**
		 * save to the database
		 */
		public function save() {

			$this->fields();
			$field_count = sizeof($this->fields);
			$row_count = sizeof($this->array);
			//$field_count = sizeof($this->fields);
			$i = 0;
			if (isset($this->array)) {
				foreach ($this->array as $row) {
					$sql = "insert into v_xml_cdr (";
					$f = 1;
					if (isset($this->fields)) {
						foreach ($this->fields as $field) {
							$field = preg_replace('#[^a-zA-Z0-9_\-]#', '', $field);
							if ($field_count == $f) {
								$sql .= "$field ";
							}
							else {
								$sql .= "$field, ";
							}
							$f++;
						}
					}
					$sql .= ")\n";
					$sql .= "values \n";
					$sql .= "(";
					$f = 1;
					if (isset($this->fields)) {
						foreach ($this->fields as $field) {
							$field = preg_replace('#[^a-zA-Z0-9_\-]#', '', $field);
							if (isset($row[$field]) && strlen($row[$field]) > 0) {
								$sql .= ":".$field." \n";
								$parameters[$field] = $row[$field];
							}
							else {
								$sql .= "null\n";
							}
							if ($field_count != $f) {
								$sql .= ",";
							}
							$f++;
						}
					}
					$sql .= ")";
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);
					$i++;
				}
			}

		}

		/**
		 * process method converts the xml cdr and adds it to the database
		 */
		public function xml_array($key, $leg, $xml_string) {

			//fix the xml by escaping the contents of <sip_full_XXX>
				if(defined('STDIN')) {
					$xml_string = preg_replace_callback("/<([^><]+)>(.*?[><].*?)<\/\g1>/",
						function ($matches) {
							return '<' . $matches[1] . '>' .
								str_replace(">", "&gt;",
									str_replace("<", "&lt;", $matches[2])
								) .
							'</' . $matches[1] . '>';
						},
						$xml_string
					);
				}

			//parse the xml to get the call detail record info
				try {
					//$this->log($xml_string);
					$xml = simplexml_load_string($xml_string);
					//$this->log("\nxml load done\n");
				}
				catch(Exception $e) {
					echo $e->getMessage();
					//$this->log("\nfail loadxml: " . $e->getMessage() . "\n");
				}

			//check for duplicate call uuid's
				$duplicate_uuid = false;
				$uuid = urldecode($xml->variables->uuid);
				if($uuid != null && is_uuid($uuid)) {
					$sql = "select count(xml_cdr_uuid) ";
					$sql .= "from v_xml_cdr ";
					$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $uuid;
					$database = new database;
					$count = $database->select($sql, $parameters, 'column');
					if ($count > 0) {
						$duplicate_uuid = true;
					}
					unset($sql, $parameters);
				}

			//process data if the call detail record is not a duplicate
				if ($duplicate_uuid == false && is_uuid($uuid)) {

					//get the destination number
						if ($xml->variables->current_application == "bridge") {
							$current_application_data = urldecode($xml->variables->current_application_data);
							$bridge_array = explode("/", $current_application_data);
							$destination_number = end($bridge_array);
							if (strpos($destination_number,'@') !== FALSE) {
								$destination_array = explode("@", $destination_number);
								$destination_number = $destination_array[0];
							}
						}
						else {
							$destination_number = urldecode($xml->variables->sip_to_user);
						}

					//if last_sent_callee_id_number is set use it for the destination_number
						if (strlen($xml->variables->last_sent_callee_id_number) > 0) {
							$destination_number = urldecode($xml->variables->last_sent_callee_id_number);
						}

					//set missed calls
						$missed_call = 'false';
						if ($xml->variables->call_direction == 'local' || $xml->variables->call_direction == 'inbound') {
							if ($xml->variables->billsec == 0) {
								$missed_call = 'true';
							}
						}
						if ($xml->variables->missed_call == 'true') {
							$missed_call = 'true';
						}

					//get the caller details
						$caller_id_name = urldecode($xml->variables->effective_caller_id_name);
						$caller_id_number = urldecode($xml->variables->effective_caller_id_number);
						$caller_id_destination = urldecode($xml->variables->caller_destination);
						foreach ($xml->callflow as $row) {
							$caller_id_number = urldecode($row->caller_profile->caller_id_number);
						}
						if (strlen($caller_id_name) == 0) {
							foreach ($xml->callflow as $row) {
								$caller_id_name = urldecode($row->caller_profile->caller_id_name);
							}
						}

					//misc
						$uuid = urldecode($xml->variables->uuid);
						$this->array[$key]['xml_cdr_uuid'] = $uuid;
						$this->array[$key]['destination_number'] = $destination_number;
						$this->array[$key]['source_number'] = urldecode($xml->variables->effective_caller_id_number);
						$this->array[$key]['user_context'] = urldecode($xml->variables->user_context);
						$this->array[$key]['network_addr'] = urldecode($xml->variables->sip_network_ip);
						$this->array[$key]['missed_call'] = $missed_call;
						$this->array[$key]['caller_id_name'] = $caller_id_name;
						$this->array[$key]['caller_id_number'] = $caller_id_number;
						$this->array[$key]['caller_destination'] = urldecode($xml->variables->caller_destination);
						$this->array[$key]['accountcode'] = urldecode($xml->variables->accountcode);
						$this->array[$key]['default_language'] = urldecode($xml->variables->default_language);
						$this->array[$key]['bridge_uuid'] = urldecode($xml->variables->bridge_uuid);
						//$this->array[$key]['digits_dialed'] = urldecode($xml->variables->digits_dialed);
						$this->array[$key]['sip_hangup_disposition'] = urldecode($xml->variables->sip_hangup_disposition);
						$this->array[$key]['pin_number'] = urldecode($xml->variables->pin_number);
					//time
						$this->array[$key]['start_epoch'] = urldecode($xml->variables->start_epoch);
						$start_stamp = urldecode($xml->variables->start_stamp);
						$this->array[$key]['start_stamp'] = $start_stamp;
						$this->array[$key]['answer_stamp'] = urldecode($xml->variables->answer_stamp);
						$this->array[$key]['answer_epoch'] = urldecode($xml->variables->answer_epoch);
						$this->array[$key]['end_epoch'] = urldecode($xml->variables->end_epoch);
						$this->array[$key]['end_stamp'] = urldecode($xml->variables->end_stamp);
						$this->array[$key]['duration'] = urldecode($xml->variables->duration);
						$this->array[$key]['mduration'] = urldecode($xml->variables->mduration);
						$this->array[$key]['billsec'] = urldecode($xml->variables->billsec);
						$this->array[$key]['billmsec'] = urldecode($xml->variables->billmsec);
					//codecs
						$this->array[$key]['read_codec'] = urldecode($xml->variables->read_codec);
						$this->array[$key]['read_rate'] = urldecode($xml->variables->read_rate);
						$this->array[$key]['write_codec'] = urldecode($xml->variables->write_codec);
						$this->array[$key]['write_rate'] = urldecode($xml->variables->write_rate);
						$this->array[$key]['remote_media_ip'] = urldecode($xml->variables->remote_media_ip);
						$this->array[$key]['hangup_cause'] = urldecode($xml->variables->hangup_cause);
						$this->array[$key]['hangup_cause_q850'] = urldecode($xml->variables->hangup_cause_q850);
					//call center
						$this->array[$key]['cc_side'] = urldecode($xml->variables->cc_side);
						$this->array[$key]['cc_member_uuid'] = urldecode($xml->variables->cc_member_uuid);
						$this->array[$key]['cc_queue_joined_epoch'] = urldecode($xml->variables->cc_queue_joined_epoch);
						$this->array[$key]['cc_queue'] = urldecode($xml->variables->cc_queue);
						$this->array[$key]['cc_member_session_uuid'] = urldecode($xml->variables->cc_member_session_uuid);
						$this->array[$key]['cc_agent'] = urldecode($xml->variables->cc_agent);
						$this->array[$key]['cc_agent_type'] = urldecode($xml->variables->cc_agent_type);
						$this->array[$key]['waitsec'] = urldecode($xml->variables->waitsec);
					//app info
						$this->array[$key]['last_app'] = urldecode($xml->variables->last_app);
						$this->array[$key]['last_arg'] = urldecode($xml->variables->last_arg);
					//conference
						$this->array[$key]['conference_name'] = urldecode($xml->variables->conference_name);
						$this->array[$key]['conference_uuid'] = urldecode($xml->variables->conference_uuid);
						$this->array[$key]['conference_member_id'] = urldecode($xml->variables->conference_member_id);
					//call quality
						$rtp_audio_in_mos = urldecode($xml->variables->rtp_audio_in_mos);
						if (strlen($rtp_audio_in_mos) > 0) {
							$this->array[$key]['rtp_audio_in_mos'] = $rtp_audio_in_mos;
						}

					//store the call leg
						$this->array[$key]['leg'] = $leg;

					//store the call direction
						$this->array[$key]['direction'] = urldecode($xml->variables->call_direction);

					//store post dial delay, in milliseconds
						$this->array[$key]['pdd_ms'] = urldecode($xml->variables->progress_mediamsec) + urldecode($xml->variables->progressmsec);

					//get break down the date to year, month and day
						$tmp_time = strtotime($start_stamp);
						$tmp_year = date("Y", $tmp_time);
						$tmp_month = date("M", $tmp_time);
						$tmp_day = date("d", $tmp_time);

					//get the domain values from the xml
						$domain_name = urldecode($xml->variables->domain_name);
						$domain_uuid = urldecode($xml->variables->domain_uuid);

					//get the domain name
						if (strlen($domain_name) == 0) {
							$domain_name = urldecode($xml->variables->dialed_domain);
						}
						if (strlen($domain_name) == 0) {
							$domain_name = urldecode($xml->variables->sip_invite_domain);
						}
						if (strlen($domain_name) == 0) {
							$domain_name = urldecode($xml->variables->sip_req_host);
						}
						if (strlen($domain_name) == 0) {
							$presence_id = urldecode($xml->variables->presence_id);
							if (strlen($presence_id) > 0) {
								$presence_array = explode($presence_id);
								$domain_name = $presence_array[1];
							}
						}

					//dynamic cdr fields
						if (is_array($_SESSION['cdr']['field'])) {
							foreach ($_SESSION['cdr']['field'] as $field) {
								$fields = explode(",", $field);
								$field_name = end($fields);
								$this->fields[] = $field_name;
								if (count($fields) == 1) {
									$this->array[$key][$field_name] = urldecode($xml->variables->$fields[0]);
								}
								if (count($fields) == 2) {
									$this->array[$key][$field_name] = urldecode($xml->$fields[0]->$fields[1]);
								}
								if (count($fields) == 3) {
									$this->array[$key][$field_name] = urldecode($xml->$fields[0]->$fields[1]->$fields[2]);
								}
							}
						}

					//send the domain name to the cdr log
						//$this->log("\ndomain_name is `$domain_name`; domain_uuid is '$domain_uuid'\n");

					//get the domain_uuid with the domain_name
						if (strlen($domain_uuid) == 0) {
							$sql = "select domain_uuid from v_domains ";
							if (strlen($domain_name) == 0 && $context != 'public' && $context != 'default') {
								$sql .= "where domain_name = :context ";
								$parameters['context'] = $context;
							}
							else {
								$sql .= "where domain_name = :domain_name ";
								$parameters['domain_name'] = $domain_name;
							}
							$database = new database;
							$domain_uuid = $database->select($sql, $parameters, 'column');
							unset($parameters);
						}

					//set values in the database
						if (strlen($domain_uuid) > 0) {
							$this->array[$key]['domain_uuid'] = $domain_uuid;
						}
						if (strlen($domain_name) > 0) {
							$this->array[$key]['domain_name'] = $domain_name;
						}

					//get the recording details
						if (strlen($xml->variables->record_session) > 0) {
							$record_path = urldecode($xml->variables->record_path);
							$record_name = urldecode($xml->variables->record_name);
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (!isset($record_path) && urldecode($xml->variables->last_app) == "record_session") {
							$record_path = dirname(urldecode($xml->variables->last_arg));
							$record_name = basename(urldecode($xml->variables->last_arg));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->record_name) > 0) {
							$record_path = urldecode($xml->variables->record_path);
							$record_name = urldecode($xml->variables->record_name);
							$record_length = urldecode($xml->variables->duration);
						}
						elseif (strlen($xml->variables->sofia_record_file) > 0) {
							$record_path = dirname(urldecode($xml->variables->sofia_record_file));
							$record_name = basename(urldecode($xml->variables->sofia_record_file));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->cc_record_filename) > 0) {
							$record_path = dirname(urldecode($xml->variables->cc_record_filename));
							$record_name = basename(urldecode($xml->variables->cc_record_filename));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->api_on_answer) > 0) {
							$command = str_replace("\n", " ", urldecode($xml->variables->api_on_answer));
							$parts = explode(" ", $command);
							if ($parts[0] == "uuid_record") {
								$recording = $parts[3];
								$record_path = dirname($recording);
								$record_name = basename($recording);
								$record_length = urldecode($xml->variables->duration);
							}
						}
						elseif (strlen($xml->variables->current_application_data) > 0) {
							$commands = explode(",", urldecode($xml->variables->current_application_data));
							foreach ($commands as $command) {
								$cmd = explode("=", $command);
								if ($cmd[0] == "api_on_answer") {
									$a = explode("]", $cmd[1]);
									$command = str_replace("'", "", $a[0]);
									$parts = explode(" ", $command);
									if ($parts[0] == "uuid_record") {
										$recording = $parts[3];
										$record_path = dirname($recording);
										$record_name = basename($recording);
										$record_length = urldecode($xml->variables->duration);
									}
								}
							}
						}
						if (!isset($record_name)) {
							$bridge_uuid = urldecode($xml->variables->bridge_uuid);
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}
						if (!isset($record_name)) {
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$uuid.'.wav')) {
								$record_path = $path;
								$record_name = $uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}

					// Last check
						 if (!isset($record_name) || is_null ($record_name) || (strlen($record_name) == 0)) {
							$bridge_uuid = urldecode($xml->variables->bridge_uuid);
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}

					//add the call recording
						if (isset($record_path) && isset($record_name) && file_exists($record_path.'/'.$record_name) && $record_length > 0) {
							//add to the xml cdr table
								$this->array[$key]['record_path'] = $record_path;
								$this->array[$key]['record_name'] = $record_name;
							//add to the call recordings table
								if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_recordings/app_config.php")) {
									//build the array
									$x = 0;
									$array['call_recordings'][$x]['call_recording_uuid'] = $uuid;
									$array['call_recordings'][$x]['domain_uuid'] = $domain_uuid;
									$array['call_recordings'][$x]['call_recording_name'] = $record_name;
									$array['call_recordings'][$x]['call_recording_path'] = $record_path;
									$array['call_recordings'][$x]['call_recording_length'] = $record_length;
									$array['call_recordings'][$x]['call_recording_date'] = urldecode($xml->variables->start_stamp);
									$array['call_recordings'][$x]['call_direction'] = urldecode($xml->variables->call_direction);
									//$array['call_recordings'][$x]['call_recording_description']= $row['zzz'];
									//$array['call_recordings'][$x]['call_recording_base64']= $row['zzz'];

									//add the temporary permission
									$p = new permissions;
									$p->add("call_recording_add", "temp");
									$p->add("call_recording_edit", "temp");

									$database = new database;
									$database->app_name = 'call_recordings';
									$database->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
									$database->domain_uuid = $domain_uuid;
									$database->save($array);
									$message = $database->message;

									//remove the temporary permission
									$p->delete("call_recording_add", "temp");
									$p->delete("call_recording_edit", "temp");
									unset($array);
								}
						}

					//save to the database in xml format
						if ($_SESSION['cdr']['format']['text'] == "xml" && $_SESSION['cdr']['storage']['text'] == "db") {
							$this->array[$key]['xml'] = $xml_string;
						}

					//save to the database in json format
						if ($_SESSION['cdr']['format']['text'] == "json" && $_SESSION['cdr']['storage']['text'] == "db") {
							$this->array[$key]['json'] = json_encode($xml);
						}

					//get the extension_uuid and then add it to the database fields array
						if (strlen($xml->variables->extension_uuid) > 0) {
							$this->array[$key]['extension_uuid'] = urldecode($xml->variables->extension_uuid);
						}
						else {
							if (strlen($xml->variables->dialed_user) > 0) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :dialed_user or number_alias = :dialed_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['dialed_user'] = $xml->variables->dialed_user;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (strlen($xml->variables->referred_by_user) > 0) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :referred_by_user or number_alias = :referred_by_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['referred_by_user'] = $xml->variables->referred_by_user;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (strlen($xml->variables->last_sent_callee_id_number) > 0) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :callee_id_number or number_alias = :last_sent_callee_id_number) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['callee_id_number'] = $xml->variables->callee_id_number;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
						}

					//insert the values
						if ($this->debug) {
							//$time5_insert = microtime(true);
							//echo $sql."<br />\n";
						}
						try {
							$error = "false";
							//$this->db->exec($sql);
						}
						catch(PDOException $e) {
							$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/failed/';
							if(!file_exists($tmp_dir)) {
								event_socket_mkdir($tmp_dir);
							}
							if ($_SESSION['cdr']['format']['text'] == "xml") {
								$tmp_file = $uuid.'.xml';
								$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
								fwrite($fh, $xml_string);
							}
							else {
								$tmp_file = $uuid.'.json';
								$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
								fwrite($fh, json_encode($xml));
							}
							fclose($fh);
							if ($this->debug) {
								echo $e->getMessage();
							}
							$error = "true";
						}

						if ($_SESSION['cdr']['storage']['text'] == "dir" && $error != "true") {
							if (strlen($uuid) > 0) {
								$tmp_time = strtotime($start_stamp);
								$tmp_year = date("Y", $tmp_time);
								$tmp_month = date("M", $tmp_time);
								$tmp_day = date("d", $tmp_time);
								$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
								if(!file_exists($tmp_dir)) {
									event_socket_mkdir($tmp_dir);
								}
								if ($_SESSION['cdr']['format']['text'] == "xml") {
									$tmp_file = $uuid.'.xml';
									$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
									fwrite($fh, $xml_string);
								}
								else {
									$tmp_file = $uuid.'.json';
									$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
									fwrite($fh, json_encode($xml));
								}
								fclose($fh);
							}
						}
						unset($error);

						//if ($this->debug) {
							//GLOBAL $insert_time,$insert_count;
							//$insert_time+=microtime(true)-$time5_insert; //add this current query.
							//$insert_count++;
						//}

				} //if ($duplicate_uuid == false)
		} //function xml_array

		/**
		 * get xml from the filesystem and save it to the database
		 */
		public function read_files() {
			$xml_cdr_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr';
			$dir_handle = opendir($xml_cdr_dir);
			$x = 0;
			while($file = readdir($dir_handle)) {
				if ($file != '.' && $file != '..') {
					if ( !is_dir($xml_cdr_dir . '/' . $file) ) {
						//get the leg of the call and the file prefix
							if (substr($file, 0, 2) == "a_") {
								$leg = "a";
								$file_prefix = substr($file, 2, 1);
							}
							else {
								$leg = "b";
								$file_prefix = substr($file, 0, 1);
							}

						//set the limit
							if (isset($_SERVER["argv"][1]) && is_numeric($_SERVER["argv"][1])) {
								$limit = $_SERVER["argv"][1];
							}
							else {
								$limit = 1;
							}

						//filter for specific files based on the file prefix
							if (isset($_SERVER["argv"][2])) {
								if (strpos($_SERVER["argv"][2], $file_prefix) !== FALSE) {
									$import = true;
								}
								else {
									$import = false;
								}
							}
							else {
								$import = true;
							}

						//import the call detail record
							if ($import) {
								//get the xml cdr string
									$xml_string = file_get_contents($xml_cdr_dir.'/'.$file);

								//parse the xml and insert the data into the db
									$this->xml_array($x, $leg, $xml_string);

								//delete the file after it has been imported
									unlink($xml_cdr_dir.'/'.$file);
							}

						//increment the value
							if ($import) {
								$x++;
							}

						//if limit exceeded exit the loop
							if ($limit == $x) {
								//echo "limit: $limit count: $x if\n";
								break;
							}
					}
				}
			}
			$this->save();
			closedir($dir_handle);
		}
		//$this->read_files();

		/**
		 * read the call detail records from the http post
		 */
		public function post() {
			if (isset($_POST["cdr"])) {
				//debug method
					if ($this->debug){
						print_r($_POST["cdr"]);
					}

				//authentication for xml cdr http post
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true" && strlen($_SESSION["xml_cdr"]["username"]) == 0) {
							//get the contents of xml_cdr.conf.xml
								$conf_xml_string = file_get_contents($_SESSION['switch']['conf']['dir'].'/autoload_configs/xml_cdr.conf.xml');

							//parse the xml to get the call detail record info
								try {
									$conf_xml = simplexml_load_string($conf_xml_string);
								}
								catch(Exception $e) {
									echo $e->getMessage();
								}
								if (isset($conf_xml->settings->param)) foreach ($conf_xml->settings->param as $row) {
									if ($row->attributes()->name == "cred") {
										$auth_array = explode(":", $row->attributes()->value);
										//echo "username: ".$auth_array[0]."<br />\n";
										//echo "password: ".$auth_array[1]."<br />\n";
									}
									if ($row->attributes()->name == "url") {
										//check name is equal to url
									}
								}
						}
					}

				//if http enabled is set to false then deny access
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "false") {
							echo "access denied<br />\n";
							return;
						}
					}

				//check for the correct username and password
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true") {
							if ($auth_array[0] == $_SERVER["PHP_AUTH_USER"] && $auth_array[1] == $_SERVER["PHP_AUTH_PW"]) {
								//echo "access granted<br />\n";
								$_SESSION["xml_cdr"]["username"] = $auth_array[0];
								$_SESSION["xml_cdr"]["password"] = $auth_array[1];
							}
							else {
								echo "access denied<br />\n";
								return;
							}
						}
					}

				//loop through all attribues
					//foreach($xml->settings->param[1]->attributes() as $a => $b) {
					//		echo $a,'="',$b,"\"<br />\n";
					//}

				//get the http post variable
					$xml_string = trim($_POST["cdr"]);

				//get the leg of the call
					if (substr($_REQUEST['uuid'], 0, 2) == "a_") {
						$leg = "a";
					}
					else {
						$leg = "b";
					}

				//log the xml cdr
					//xml_cdr_log("process cdr via post\n");

				//parse the xml and insert the data into the database
					$this->xml_array(0, $leg, $xml_string);
					$this->save();
			}
		}
		//$this->post();

		/**
		 * user summary returns an array
		 */
		public function user_summary() {

			//build the date range
				if (strlen($this->start_stamp_begin) > 0 || strlen($this->start_stamp_end) > 0) {
					unset($this->quick_select);
					if (strlen($this->start_stamp_begin) > 0 && strlen($this->start_stamp_end) > 0) {
						$sql_date_range .= " and start_stamp between :start_stamp_begin and :start_stamp_end \n";
						$parameters['start_stamp_begin'] = $this->start_stamp_begin.':00.000';
						$parameters['start_stamp_end'] = $this->start_stamp_end.':59.999';
					}
					else {
						if (strlen($this->start_stamp_begin) > 0) { $sql_date_range .= "and start_stamp >= :start_stamp_begin \n"; }
						if (strlen($this->start_stamp_end) > 0) { $sql_date_range .= "and start_stamp <= :start_stamp_end \n"; }
						$parameters['start_stamp_begin'] = $this->start_stamp_begin.':00.000';
						$parameters['start_stamp_end'] = $this->start_stamp_end.':59.999';
					}
				}
				else {
					switch ($this->quick_select) {
						case 1: $sql_date_range .= "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 week"))."' \n"; break; //last 7 days
						case 2: $sql_date_range .= "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 hour"))."' \n"; break; //last hour
						case 3: $sql_date_range .= "and start_stamp >= '".date('Y-m-d')." "."00:00:00.000' \n"; break; //today
						case 4: $sql_date_range .= "and start_stamp between '".date('Y-m-d',strtotime("-1 day"))." "."00:00:00.000' and '".date('Y-m-d',strtotime("-1 day"))." "."23:59:59.999' \n"; break; //yesterday
						case 5: $sql_date_range .= "and start_stamp >= '".date('Y-m-d',strtotime("this week"))." "."00:00:00.000' \n"; break; //this week
						case 6: $sql_date_range .= "and start_stamp >= '".date('Y-m-')."01 "."00:00:00.000' \n"; break; //this month
						case 7: $sql_date_range .= "and start_stamp >= '".date('Y-')."01-01 "."00:00:00.000' \n"; break; //this year
					}
				}

			//calculate the summary data
				$sql = "select \n";
				$sql .= "e.domain_uuid, \n";
				$sql .= "d.domain_name, \n";
				$sql .= "e.extension, \n";
				$sql .= "e.number_alias, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and missed_call = false \n";
				if ($this->include_internal) {
					$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
					$sql .= "and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as answered, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and missed_call = true \n";
				if ($this->include_internal) {
							$sql .= " and (direction = 'inbound' or direction = 'local') ";
				} else {
							$sql .= " and direction = 'inbound' ";
				}
				$sql .= ") \n";
				$sql .= "as missed, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.hangup_cause = 'NO_ANSWER' \n";
 				if ($this->include_internal) {
					$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else { 
					$sql .= "and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as no_answer, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.hangup_cause = 'USER_BUSY' \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
						$sql .= " and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as busy, \n";

				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if (!$this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= " ) / \n";
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if (!$this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= ") \n";
				$sql .= "as aloc, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
						$sql .= " and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as inbound_calls, \n";

				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local')) \n";
				}
				else {
						$sql .= " and direction = 'inbound') \n";
				}
				$sql .= "as inbound_duration, \n";

				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "as outbound_calls, \n";

				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "as outbound_duration, \n";

				$sql .= "e.description \n";

				$sql .= "from v_extensions as e, v_domains as d, \n";
				$sql .= "( select \n";
				$sql .= " domain_uuid, \n";
				$sql .= " extension_uuid, \n";
				$sql .= " caller_id_number, \n";
				$sql .= " destination_number, \n";
				$sql .= " missed_call, \n";
				$sql .= " answer_stamp, \n";
				$sql .= " bridge_uuid, \n";
				$sql .= " direction, \n";
				$sql .= " start_stamp, \n";
				$sql .= " hangup_cause, \n";
				$sql .= " billsec \n";
				$sql .= " from v_xml_cdr \n";
				$sql .= " where domain_uuid = :domain_uuid \n";
				$sql .= $sql_date_range;
				$sql .= ") as c \n";

				$sql .= "where \n";
				$sql .= "d.domain_uuid = e.domain_uuid \n";
				if (!($_GET['showall'] && permission_exists('xml_cdr_all'))) {
					$sql .= "and e.domain_uuid = :domain_uuid \n";
				}
				$sql .= "group by e.extension, e.domain_uuid, d.domain_uuid, e.number_alias, e.description \n";
				$sql .= "order by extension asc \n";

				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$summary = $database->select($sql, $parameters, 'all');
				unset($parameters);

			//return the array
				return $summary;
		}

		/**
		 * download the recordings
		 */
		public function download($uuid) {
			if (permission_exists('xml_cdr_view')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					if (is_uuid($uuid)) {
						$sql = "select record_name, record_path from v_xml_cdr ";
						$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
						//$sql .= "and domain_uuid = '".$domain_uuid."' \n";
						$parameters['xml_cdr_uuid'] = $uuid;
						//$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$row = $database->select($sql, $parameters, 'row');
						if (is_array($row)) {
							$record_name = $row['record_name'];
							$record_path = $row['record_path'];
						}
						unset ($sql, $parameters, $row);
					}

				//build full path
					$record_file = $record_path . '/' . $record_name;

				//download the file
					if (file_exists($record_file)) {
						//content-range
						//if (isset($_SERVER['HTTP_RANGE']))  {
						//	range_download($record_file);
						//}
						ob_clean();
						$fd = fopen($record_file, "rb");
						if ($_GET['t'] == "bin") {
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");
						}
						else {
							$file_ext = substr($record_name, -3);
							if ($file_ext == "wav") {
								header("Content-Type: audio/x-wav");
							}
							if ($file_ext == "mp3") {
								header("Content-Type: audio/mpeg");
							}
							if ($file_ext == "ogg") {
								header("Content-Type: audio/ogg");
							}
						}
						$record_name = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $record_name);
						header('Content-Disposition: attachment; filename="'.$record_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						header("Content-Length: " . filesize($record_file));
						ob_clean();
						fpassthru($fd);
					}
			}
		} //end download method

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						$records_deleted = 0;

						//loop through records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//get the call recordings
										$sql = "select * from v_call_recordings ";
										$sql .= "where call_recording_uuid = :xml_cdr_uuid ";
										$parameters['xml_cdr_uuid'] = $record['uuid'];
										$database = new database;
										$row = $database->select($sql, $parameters, 'row');
										unset($sql, $parameters);

									//delete the call recording (file)
										$call_recording_path = realpath($row['call_recording_path']);
										$call_recording_name = $row['call_recording_name'];
										if (file_exists($call_recording_path.'/'.$call_recording_name)) {
											@unlink($call_recording_path.'/'.$call_recording_name);
										}

									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['call_recordings'][$x]['call_recording_uuid'] = $record['uuid'];

									//increment counter
										$records_deleted++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('call_recording_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('call_recording_delete', 'temp');

								//set message
									message::add($text['message-delete'].": ".$records_deleted);
							}
							unset($records);
					}
			}
		}

	} //end the class
}
/*
//example use
	$cdr = new xml_cdr;
	$cdr->read_files();
*/

?>
