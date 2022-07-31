<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->database();
		$this->load->helper('url');

		$this->load->library('grocery_CRUD','email');
		
	}

	public function _example_output($output = null)
	{
		$this->load->view('content_crud_admin',(array)$output);
	}
	

	public function index()
	{
		$this->_example_output((object)array('output' => '' , 'js_files' => array() , 'css_files' => array()));
	}

	public function jabatan(){
		$crud = new grocery_CRUD();
		 $crud->set_table('jabatan');
		 $crud->set_subject('Data Jabatan');	
		 $crud->required_fields('nama_jabatan');

		 $crud->unset_print();
		 $crud->unset_clone();
		 $crud->unset_export();
				 
		 $output = $crud->render();
		 $this->_example_output($output);
	}

	function cek_nik($post_array){
		$nik= $post_array['nik'];
		$i = $this->db->query("SELECT * FROM pegawai where nik = '$nik'")->num_rows();
		if($i == 0 ){
		 return true;
		}else{
		 return false;
		}
	}

	public function pegawai(){
		$crud = new grocery_CRUD();
		$crud->set_model('Custom_grocery_crud_model');
		$crud->basic_model->set_custom_query("SELECT * FROM pegawai where role = 'pegawai'");

		 $crud->set_table('pegawai');
		 $crud->set_subject('Data Pegawai');	
		 $crud->required_fields('username','password','nik','nama','tempat_lahir','tanggal_lahir','jenis_kelamin','agama','no_telepon','alamat','jabatan','email');

		$crud->callback_add_field('username',function(){
			$data=$this->db->query("SELECT MAX(id) as username1 FROM pegawai")->row_array();
			$data2=$data['username1']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$data3 = "PGW-".$fzeropadded;
			return '<input type="text" id="username" style="height: 40px;" readonly value="'.$data3.'" name="username">';});

		
		$crud->callback_edit_field('username',function($value, $primary_key){
			return '  <input type="text" id="username" readonly value="'.$value.'" name="username"  style="height: 40px;">';});

		$crud->change_field_type('password', 'password');
		
		// $crud->callback_before_insert(array($this,'cek_nik'));
		// $crud->callback_before_update(array($this,'cek_nik'));

		 $crud->callback_add_field('jabatan',function(){
			return '  <input type="text" id="jabatan" readonly name="jabatan"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#JabatModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('jabatan',function($value, $primary_key){
			return '  <input type="text" id="jabatan" readonly value="'.$value.'" name="jabatan"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#JabatModal"><b>Cari Data</b></button>';});

		$crud->callback_after_insert(array($this, 'sendmail_pegawai'));
		$crud->callback_after_update(array($this, 'sendmail_pegawai'));

		 $crud->set_field_upload('foto','assets/uploads/images');

		$crud->field_type('jenis_kelamin','enum',array('Laki-Laki','Perempuan'));
		$crud->field_type('agama','enum',array('Islam','Kristen','Budha','Hindu','Khonghucu'));
		$crud->field_type('role','hidden','pegawai');
 
		 $crud->unset_columns('role');
		 $crud->unset_print();
		 $crud->unset_clone();
		 $crud->unset_export();
				 
		 $output = $crud->render();
		 $this->_example_output($output);
	}

	public function sendmail_pegawai($post_array,$primary_key)
	{
		$email = $this->input->post('email');

		$username=$this->input->post('username');
		$password=$this->input->post('password');
		$nik=$this->input->post('nik');
		$nama=$this->input->post('nama');
		$jabatan=$this->input->post('jabatan');
		$no_telepon=$this->input->post('no_telepon');
		$message = '<center>
						<h4>Dinas Pariwisata Tanah Laut</h4>
						<h4>Informasi data anda sebagai berikut :</h4>
						<table border="0">
						<tr> 
						<td>Username</td>
						<td>:</td>
						<td>'.$username.'</td>
						</tr>
						<tr>
						<td>Password</td>
						<td>:</td>
						<td>'.$password.'</td>
						</tr>
						<tr>
						<td>NIK</td>
						<td>:</td>
						<td>'.$nik.'</td>
						</tr>
						<tr>
						<td>Nama</td>
						<td>:</td>
						<td>'.$nama.'</td>
						</tr>
						<tr>
						<td>Jabatan</td>
						<td>:</td>
						<td>'.$jabatan.'</td>
						</tr>

						</h3>
					</center>';

		$this->email->from('layananmail3@gmail.com', 'Layanan@3');
		$this->email->to($email);

		$this->email->subject('Konfirmasi Email');
		$this->email->message($message);

		$this->email->set_mailtype('html');
		$this->email->send();

	}

	public function hak_akses(){
		$crud = new grocery_CRUD();
		// $id=$this->session->userdata("user_id");
		$crud->set_model('Custom_grocery_crud_model');
		$crud->basic_model->set_custom_query("SELECT * FROM pegawai where role != 'pegawai'");

		$crud->set_table('pegawai');
		 $crud->set_subject('Setting Hak Akses');	
		 $crud->required_fields('username','password','nama','role','no_telepon','email');
		 $crud->columns('username','password','nama','role','no_telepon','email');

		$crud->change_field_type('password', 'password');
		
		// $crud->callback_before_insert(array($this,'cek_nik'));
		// $crud->callback_before_update(array($this,'cek_nik'));

		$crud->callback_after_insert(array($this, 'sendmail_admin'));
		$crud->callback_after_update(array($this, 'sendmail_admin'));

		//  $crud->set_field_upload('foto','assets/uploads/images');

		$crud->field_type('role','enum',array('admin','kepala dinas'));
		$crud->field_type('nik','hidden');
		$crud->field_type('tempat_lahir','hidden');
		$crud->field_type('tanggal_lahir','hidden');
		$crud->field_type('jenis_kelamin','hidden');
		$crud->field_type('agama','hidden');
		$crud->field_type('alamat','hidden');
		$crud->field_type('jabatan','hidden');
		$crud->field_type('foto','hidden');
 
		 $crud->unset_print();
		 $crud->unset_clone();
		 $crud->unset_export();
				 
		 $output = $crud->render();
		 $this->_example_output($output);
	}

	
	public function sendmail_admin($post_array,$primary_key)
	{
		$email = $this->input->post('email');

		$username=$this->input->post('username');
		$password=$this->input->post('password');
		$nama=$this->input->post('nama');
		$message = '<center>
						<h4>Dinas Pariwisata Tanah Laut</h4>
						<h4>Informasi data anda sebagai berikut :</h4>
						<table border="0">
						<tr> 
						<td>Username</td>
						<td>:</td>
						<td>'.$username.'</td>
						</tr>
						<tr>
						<td>Password</td>
						<td>:</td>
						<td>'.$password.'</td>
						</tr>
						<tr>
						<td>Nama</td>
						<td>:</td>
						<td>'.$nama.'</td>
						</tr>

						</h3>
					</center>';

		$this->email->from('layananmail3@gmail.com', 'Layanan@3');
		$this->email->to($email);

		$this->email->subject('Konfirmasi Email');
		$this->email->message($message);

		$this->email->set_mailtype('html');
		$this->email->send();

	}

	
	public function kirim_wa($post_array,$primary_key){
		{
			$username=$this->input->post('username');
			$password=$this->input->post('password');
			$nik=$this->input->post('nik');
			$nama=$this->input->post('nama');
			$jabatan=$this->input->post('jabatan');
			$no_telepon=$this->input->post('no_telepon');
						
			$userkey = 'a33b1cc57b1e';
			$passkey = '';
			$telepon = $no_telepon;
			$message = "Informasi data anda. Username:$username. Password:$password. NIK:$nik. Nama:$nama. Jabatan:$jabatan.";
			$url = 'https://console.zenziva.net/wareguler/api/sendWA/';
			$curlHandle = curl_init();
			curl_setopt($curlHandle, CURLOPT_URL, $url);
			curl_setopt($curlHandle, CURLOPT_HEADER, 0);
			curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
			curl_setopt($curlHandle, CURLOPT_POST, 1);
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
				'userkey' => $userkey,
    			'passkey' => $passkey,
    			'to' => $telepon,
    			'message' => $message
			));
			$results = json_decode(curl_exec($curlHandle), true);
			curl_close($curlHandle);
		}
	}
	
	public function kirim_wa_admin($post_array,$primary_key){
		{
			$username=$this->input->post('username');
			$password=$this->input->post('password');
			$nama=$this->input->post('nama');
			$no_telepon=$this->input->post('no_telepon');
						
			$userkey = 'a33b1cc57b1e';
			$passkey = '';
			$telepon = $no_telepon;
			$message = "Informasi data anda. Username:$username. Password:$password. Nama:$nama";
			$url = 'https://console.zenziva.net/wareguler/api/sendWA/';
			$curlHandle = curl_init();
			curl_setopt($curlHandle, CURLOPT_URL, $url);
			curl_setopt($curlHandle, CURLOPT_HEADER, 0);
			curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
			curl_setopt($curlHandle, CURLOPT_POST, 1);
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
				'userkey' => $userkey,
    			'passkey' => $passkey,
    			'to' => $telepon,
    			'message' => $message,
			));
			$results = json_decode(curl_exec($curlHandle), true);
			curl_close($curlHandle);
		}
	}


	function cek_no_surat_masuk($post_array){
		$no_surat= $post_array['no_surat'];
		$i = $this->db->query("SELECT * FROM surat_masuk where no_surat = '$no_surat'")->num_rows();
		if($i == 0 ){
		 return true;
		}else{
		 return false;
		}
	}

	
	public function _nama($value, $row){
		$data = array();
		$cab=$this->db->query("SELECT * FROM pegawai")->result_array();
		foreach ($cab as $value) {
			if ($value['nik']==$row->nik) {
				$tes=$value['nama'];
			}
		}
		if(empty($tes)){
			$tes='';
		}
		return $tes;
	}
	
	public function _no_telepon($value, $row){
		$data = array();
		$cab=$this->db->query("SELECT * FROM pegawai")->result_array();
		foreach ($cab as $value) {
			if ($value['nik']==$row->nik) {
				$tes=$value['no_telepon'];
			}
		}
		if(empty($tes)){
			$tes='';
		}
		return $tes;
	}
	
	public function _jabatan($value, $row){
		$data = array();
		$cab=$this->db->query("SELECT * FROM pegawai")->result_array();
		foreach ($cab as $value) {
			if ($value['nik']==$row->nik) {
				$tes=$value['jabatan'];
			}
		}
		if(empty($tes)){
			$tes='';
		}
		return $tes;
	}

	
	public function kegiatan_pegawai(){
		$crud = new grocery_CRUD();
		$crud->set_table('kegiatan_pegawai');
		$crud->set_subject('Data Kegiatan Pegawai');	
		$crud->required_fields('kode_kegiatan','nik','nama','no_telepon','jabatan','nama_kegiatan','tanggal_kegiatan','tempat_kegiatan','alamat_kegiatan');

		$crud->columns('kode_kegiatan','nik','nama','no_telepon','jabatan','nama_kegiatan','tanggal_kegiatan','tempat_kegiatan','alamat_kegiatan','verifikasi');

		$crud->callback_column('nama',array($this,'_nama'));
		$crud->callback_column('no_telepon',array($this,'_no_telepon'));
		$crud->callback_column('jabatan',array($this,'_jabatan'));

		$crud->callback_add_field('verifikasi',function(){
			return '<input type="hidden" id="verifikasi" value="Diajukan" name="verifikasi" >';});
		
		$crud->callback_edit_field('verifikasi',function($value, $primary_key){
			return '  <input type="hidden" id="verifikasi" value="'.$value.'" name="verifikasi">';});
			
		$crud->field_type('verifikasi','hidden');

		$crud->callback_add_field('kode_kegiatan',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM kegiatan_pegawai")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$nosurat2 = $fzeropadded."/".$now;
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="kode_kegiatan" id="kode_kegiatan">';});
		
		$crud->callback_edit_field('kode_kegiatan',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="kode_kegiatan" id="kode_kegiatan"> ';});

			$crud->callback_add_field('nik',function(){
			return '  <input type="text" id="nik" readonly name="nik"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#kegiatanModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('nik',function($value, $primary_key){
			return '  <input type="text" id="nik" readonly value="'.$value.'" name="nik"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#kegiatanModal"><b>Cari Data</b></button>';});

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}
	

	public function surat_masuk(){
		$crud = new grocery_CRUD();
		$crud->set_table('surat_masuk');
		$crud->set_subject('Data Surat Masuk');	
		$crud->required_fields('no_surat','tanggal_surat','tanggal_terima','pengirim','sifat','isi');
		$crud->field_type('sifat','enum',array('BIASA', 'RAHASIA', 'SANGAT RAHASIA', 'SEGERA', 'PENTING'));
		
		$crud->set_field_upload('lampiran','assets/uploads/images');
		$crud->callback_before_insert(array($this,'cek_no_surat_masuk'));

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}

	public function disposisi(){
		$crud = new grocery_CRUD();
		 $crud->set_table('disposisi_surat');
		 $crud->set_subject('Data Disposisi Surat Masuk');	
		 $crud->required_fields('no_surat','tanggal_surat','pengirim','perihal','sifat','tanggal_disposisi','isi_disposisi');
		 
		 $crud->callback_add_field('no_surat',function(){
			return '  <input type="text" id="no_surat" readonly name="no_surat"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#disposisiModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '  <input type="text" id="no_surat" readonly value="'.$value.'" name="no_surat"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#disposisiModal"><b>Cari Data</b></button>';});

		$crud->unset_operations();
		// $crud->unset_print();
		// $crud->unset_clone();
		// $crud->unset_export();
				 
		 $output = $crud->render();
		 $this->_example_output($output);
	}

	public function surat_keluar(){
		$crud = new grocery_CRUD();
		$crud->set_table('surat_keluar');
		$crud->set_subject('Data Surat Keluar');	
		$crud->required_fields('no_surat','tujuan','tanggal_surat','tanggal_terima','sifat','perihal');
		$crud->field_type('sifat','enum',array('BIASA', 'RAHASIA', 'SANGAT RAHASIA', 'SEGERA', 'PENTING'));

		$crud->callback_add_field('verifikasi',function(){
			return '<input type="hidden" id="verifikasi" value="Diajukan" name="verifikasi" >';});
		
		$crud->callback_edit_field('verifikasi',function($value, $primary_key){
			return '  <input type="hidden" id="verifikasi" value="'.$value.'" name="verifikasi">';});
			
		$crud->field_type('verifikasi','hidden');

		$crud->callback_add_field('no_surat',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM surat_keluar")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$nosurat2 = $fzeropadded."/SK"."/".$now;
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="no_surat" id="no_surat">';});
		
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="no_surat" id="no_surat"> ';});

		$crud->set_field_upload('lampiran','assets/uploads/images');

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}

	public function surat_balasan(){
		$crud = new grocery_CRUD();
		$crud->set_table('surat_balasan');
		$crud->set_subject('Data Surat Balasan Dari Surat Keluar');	
		$crud->required_fields('no_surat','tujuan','tanggal','no_surat_keluar','sifat','perihal','keterangan_balasan');

		$crud->callback_add_field('no_surat',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM surat_balasan")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$nosurat2 = $fzeropadded."/SB"."/".$now;
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="no_surat" id="no_surat">';});
		
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="no_surat" id="no_surat"> ';});

		
		 $crud->callback_add_field('no_surat_keluar',function(){
			return '  <input type="text" id="no_surat_keluar" readonly name="no_surat_keluar"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#suratKeluar"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('no_surat_keluar',function($value, $primary_key){
			return '  <input type="text" id="no_surat_keluar" readonly value="'.$value.'" name="no_surat_keluar"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#suratKeluar"><b>Cari Data</b></button>';});


		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}


	public function sppd(){
		$crud = new grocery_CRUD();
		$crud->set_table('sppd');
		$crud->set_subject('Data Surat Perintah Perjalanan Dinas');	
		$crud->required_fields('no_surat','tanggal_surat','tujuan','maksud','lampiran','tanggal_berangkat','tanggal_kembali','keperluan','nik','nama','jabatan');

		$crud->columns('no_surat','tanggal_surat','tujuan','nik','nama','no_telepon','jabatan','tanggal_berangkat','tanggal_kembali','keperluan','lampiran','catatan_kepala_dinas','verifikasi');

		$crud->callback_column('nama',array($this,'_nama'));
		$crud->callback_column('no_telepon',array($this,'_no_telepon'));
		$crud->callback_column('jabatan',array($this,'_jabatan'));

		$crud->callback_after_insert(array($this, 'sendmail_sppd'));
		$crud->callback_after_update(array($this, 'sendmail_sppd'));
		
		$crud->callback_add_field('no_surat',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM sppd")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$nosurat2 = $fzeropadded."/SPPD"."/".$now;
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="no_surat" id="no_surat">';});
		
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="no_surat" id="no_surat"> ';});
		
		$crud->field_type('verifikasi','enum',array('Belum Diverifikasi','Ya','Tidak'));

		$crud->callback_add_field('verifikasi',function(){
			return '<input type="hidden" id="verifikasi" value="Belum Diverifikasi" name="verifikasi" >';});
		
		$crud->callback_edit_field('verifikasi',function($value, $primary_key){
			return '  <input type="hidden" id="verifikasi" value="'.$value.'" name="verifikasi">';});
			
		$crud->field_type('verifikasi','hidden');

		$crud->set_field_upload('lampiran','assets/uploads/images');

		
		$crud->callback_add_field('nik',function(){
			return '  <input type="text" id="nik" readonly name="nik"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sppdModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('nik',function($value, $primary_key){
			return '  <input type="text" id="nik" readonly value="'.$value.'" name="nik"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sppdModal"><b>Cari Data</b></button>';});

		$crud->callback_add_field('nama',function(){
			return '  <input type="text" id="nama" readonly name="nama" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('nama',function($value, $primary_key){
			return '  <input type="text" id="nama" readonly value="'.$value.'" name="nama" style="height: 40px; width: 500px;">';});

		$crud->callback_add_field('jabatan',function(){
			return '  <input type="text" id="jabatan" readonly name="jabatan" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('jabatan',function($value, $primary_key){
			return '  <input type="text" id="jabatan" readonly value="'.$value.'" name="jabatan" style="height: 40px; width: 500px;">';});

		$crud->callback_add_field('catatan_kepala_dinas',function(){
			return '  <input type="hidden" id="catatan_kepala_dinas" value="" readonly name="catatan_kepala_dinas" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('catatan_kepala_dinas',function($value, $primary_key){
			return '  <input type="hidden" id="catatan_kepala_dinas" readonly value="'.$value.'" name="catatan_kepala_dinas" style="height: 40px; width: 500px;">';});
			

		$crud->field_type('catatan_kepala_dinas','hidden');

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}

	
	public function sendmail_sppd($post_array,$primary_key)
	{
		$data = $this->db->query("SELECT * FROM pegawai, sppd where pegawai.nik = sppd.nik")->result_array();
		foreach ($data as $key) {
			$email = $key['email'];
		}

		// $nik1=$this->input->post('nik');
		$tujuan=$this->input->post('tujuan');
		$tanggal_berangkat=$this->input->post('tanggal_berangkat');
		$tanggal_kembali=$this->input->post('tanggal_kembali');
		$keperluan=$this->input->post('keperluan');

		$message = '<center>
						<h4>Dinas Pariwisata Tanah Laut</h4>
						<h4>Berikut Informasi Surat Perintah Perjalanan Dinas Anda :</h4>
						<table border="0">
						<tr> 
						<td>Tujuan</td>
						<td>:</td>
						<td>'.$tujuan.'</td>
						</tr>
						<tr>
						<td>Tanggal Berangkat</td>
						<td>:</td>
						<td>'.$tanggal_berangkat.'</td>
						</tr>
						<tr>
						<td>Tanggal Kembali</td>
						<td>:</td>
						<td>'.$tanggal_kembali.'</td>
						</tr>
						<tr>
						<td>Keperluan</td>
						<td>:</td>
						<td>'.$keperluan.'</td>
						</tr>

						</h3>
					</center>';

		$this->email->from('layananmail3@gmail.com', 'Layanan@3');
		$this->email->to($email);

		$this->email->subject('Informasi');
		$this->email->message($message);

		$this->email->set_mailtype('html');
		$this->email->send();

	}


	
	public function kirim_wa_sppd($post_array,$primary_key){
		{
			$tujuan=$this->input->post('tujuan');
			$tanggal_berangkat=$this->input->post('tanggal_berangkat');
			$tanggal_kembali=$this->input->post('tanggal_kembali');
			$keperluan=$this->input->post('keperluan');
			$no_telepon=$this->input->post('no_telepon');
						
			$userkey = 'a33b1cc57b1e';
			$passkey = '';
			$telepon = $no_telepon;
			$message = "Berikut Surat Perintah Perjalanan Dinas Anda. Tujuan:$tujuan. Tanggal:$tanggal_berangkat Sampai $tanggal_kembali.  Keperluan:$keperluan.";
			$url = 'https://console.zenziva.net/wareguler/api/sendWA/';
			$curlHandle = curl_init();
			curl_setopt($curlHandle, CURLOPT_URL, $url);
			curl_setopt($curlHandle, CURLOPT_HEADER, 0);
			curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
			curl_setopt($curlHandle, CURLOPT_POST, 1);
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
				'userkey' => $userkey,
    			'passkey' => $passkey,
    			'to' => $telepon,
    			'message' => $message,
			));
			$results = json_decode(curl_exec($curlHandle), true);
			curl_close($curlHandle);
		}
	}

	public function surat_tugas(){
		$crud = new grocery_CRUD();
		$crud->set_table('surat_tugas');
		$crud->set_subject('Data Surat Tugas');	
		$crud->required_fields('no_surat','tanggal_surat','tujuan','maksud','lampiran','tanggal_berangkat','tanggal_kembali','keperluan','nik');

		$crud->columns('no_surat','tanggal_surat','nik','nama','no_telepon','jabatan','tujuan','tanggal_berangkat','tanggal_kembali','keperluan','lampiran','catatan_kepala_dinas','verifikasi');

		$crud->callback_column('nama',array($this,'_nama'));
		$crud->callback_column('no_telepon',array($this,'_no_telepon'));
		$crud->callback_column('jabatan',array($this,'_jabatan'));

		$crud->callback_after_insert(array($this, 'sendmail_tugas'));
		$crud->callback_after_update(array($this, 'sendmail_tugas'));
		
		$crud->callback_add_field('no_surat',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM surat_tugas")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$now = date('Y'); 
			$nosurat2 = $fzeropadded."/ST"."/".$now;
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="no_surat" id="no_surat">';});
		
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="no_surat" id="no_surat"> ';});
		
		// $crud->field_type('verifikasi','enum',array('Belum Diverifikasi','Ya','Tidak'));

		$crud->callback_add_field('verifikasi',function(){
			return '<input type="hidden" id="verifikasi" value="Diajukan" name="verifikasi" >';});
		
		$crud->callback_edit_field('verifikasi',function($value, $primary_key){
			return '  <input type="hidden" id="verifikasi" value="'.$value.'" name="verifikasi">';});
			
		$crud->field_type('verifikasi','hidden');

		$crud->callback_add_field('nik',function(){
			return '  <input type="text" id="nik" readonly name="nik"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tugasModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('nik',function($value, $primary_key){
			return '  <input type="text" id="nik" readonly value="'.$value.'" name="nik"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tugasModal"><b>Cari Data</b></button>';});

		$crud->callback_add_field('nama',function(){
			return '  <input type="text" id="nama" readonly name="nama" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('nama',function($value, $primary_key){
			return '  <input type="text" id="nama" readonly value="'.$value.'" name="nama" style="height: 40px; width: 500px;">';});

		$crud->callback_add_field('jabatan',function(){
			return '  <input type="text" id="jabatan" readonly name="jabatan" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('jabatan',function($value, $primary_key){
			return '  <input type="text" id="jabatan" readonly value="'.$value.'" name="jabatan" style="height: 40px; width: 500px;">';});
		
		$crud->callback_add_field('catatan_kepala_dinas',function(){
			return '  <input type="hidden" id="catatan_kepala_dinas" value="" readonly name="catatan_kepala_dinas" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('catatan_kepala_dinas',function($value, $primary_key){
			return '  <input type="hidden" id="catatan_kepala_dinas" readonly value="'.$value.'" name="catatan_kepala_dinas" style="height: 40px; width: 500px;">';});
			
		$crud->field_type('catatan_kepala_dinas','hidden');
			

		$crud->set_field_upload('lampiran','assets/uploads/images');

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}

	
	public function sendmail_tugas($post_array,$primary_key)
	{
		$data = $this->db->query("SELECT * FROM pegawai, surat_tugas where pegawai.nik = surat_tugas.nik")->result_array();
		foreach ($data as $key) {
			$email = $key['email'];
		}

		// $nik1=$this->input->post('nik');
		$tujuan=$this->input->post('tujuan');
		$tanggal_berangkat=$this->input->post('tanggal_berangkat');
		$tanggal_kembali=$this->input->post('tanggal_kembali');
		$keperluan=$this->input->post('keperluan');

		$message = '<center>
						<h4>Dinas Pariwisata Tanah Laut</h4>
						<h4>Berikut Informasi Surat Tugas Anda :</h4>
						<table border="0">
						<tr> 
						<td>Tujuan</td>
						<td>:</td>
						<td>'.$tujuan.'</td>
						</tr>
						<tr>
						<td>Tanggal Berangkat</td>
						<td>:</td>
						<td>'.$tanggal_berangkat.'</td>
						</tr>
						<tr>
						<td>Tanggal Kembali</td>
						<td>:</td>
						<td>'.$tanggal_kembali.'</td>
						</tr>
						<tr>
						<td>Keperluan</td>
						<td>:</td>
						<td>'.$keperluan.'</td>
						</tr>

						</h3>
					</center>';

		$this->email->from('layananmail3@gmail.com', 'Layanan@3');
		$this->email->to($email);

		$this->email->subject('Informasi');
		$this->email->message($message);

		$this->email->set_mailtype('html');
		$this->email->send();

	}


	
	public function kirim_wa_tugas($post_array,$primary_key){
		{
			$tujuan=$this->input->post('tujuan');
			$tanggal_berangkat=$this->input->post('tanggal_berangkat');
			$tanggal_kembali=$this->input->post('tanggal_kembali');
			$keperluan=$this->input->post('keperluan');
			$no_telepon=$this->input->post('no_telepon');
						
			$userkey = 'a33b1cc57b1e';
			$passkey = '212192831b1dba6a8a9a6565';
			$telepon = $no_telepon;
			$message = "Berikut Surat Tugas Anda. Tujuan:$tujuan. Tanggal:$tanggal_berangkat Sampai $tanggal_kembali.  Keperluan:$keperluan.";
			$url = 'https://console.zenziva.net/wareguler/api/sendWA/';
			$curlHandle = curl_init();
			curl_setopt($curlHandle, CURLOPT_URL, $url);
			curl_setopt($curlHandle, CURLOPT_HEADER, 0);
			curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
			curl_setopt($curlHandle, CURLOPT_POST, 1);
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
				'userkey' => $userkey,
    			'passkey' => $passkey,
    			'to' => $telepon,
    			'message' => $message,
			));
			$results = json_decode(curl_exec($curlHandle), true);
			curl_close($curlHandle);
		}
	}


	public function surat_izin(){
		$crud = new grocery_CRUD();
		$crud->set_table('surat_izin');
		$crud->set_subject('Data Surat Izin');	
		$crud->required_fields('no_surat','tanggal_surat','tanggal_terima','nik','nama','tanggal_berangkat','jabatan','tanggal_mulai_izin','tanggal_selesai_izin','tanggal_mulai_izin');
		
		$crud->callback_add_field('no_surat',function(){
			$data=$this->db->query("SELECT MAX(id) as kd FROM surat_izin")->row_array();
			$data2=$data['kd']+1;
			$fzeropadded = sprintf("%03d", $data2);
			$nosurat2 = $fzeropadded."/SI/2021";
			return '<input type="text" style="height: 40px" readonly value="'.$nosurat2.'" name="no_surat" id="no_surat">';});
		
		$crud->callback_edit_field('no_surat',function($value, $primary_key){
			return '<input type="text" style="height: 40px" readonly value="'.$value.'" name="no_surat" id="no_surat"> ';});
		
		// $crud->field_type('verifikasi','enum',array('Belum Diverifikasi','Ya','Tidak'));

		$crud->callback_add_field('verifikasi',function(){
			return '<input type="hidden" id="verifikasi" value="Diajukan" name="verifikasi" >';});
		
		$crud->callback_edit_field('verifikasi',function($value, $primary_key){
			return '  <input type="hidden" id="verifikasi" value="'.$value.'" name="verifikasi">';});
			
		$crud->field_type('verifikasi','hidden');

		$crud->set_field_upload('lampiran','assets/uploads/images');

		$crud->callback_add_field('nik',function(){
			return '  <input type="text" id="nik" readonly name="nik"  style="height: 40px; width: 400px;">&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#NIKModal"><b>Cari Data</b></button>';});
			
		$crud->callback_edit_field('nik',function($value, $primary_key){
			return '  <input type="text" id="nik" readonly value="'.$value.'" name="nik"  style="height: 40px; width: 400px;" >&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#NIKModal"><b>Cari Data</b></button>';});

		$crud->callback_add_field('nama',function(){
			return '  <input type="text" id="nama" readonly name="nama" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('nama',function($value, $primary_key){
			return '  <input type="text" id="nama" readonly value="'.$value.'" name="nama" style="height: 40px; width: 500px;">';});

		$crud->callback_add_field('jabatan',function(){
			return '  <input type="text" id="jabatan" readonly name="jabatan" style="height: 40px; width: 500px;">';});
			
		$crud->callback_edit_field('jabatan',function($value, $primary_key){
			return '  <input type="text" id="jabatan" readonly value="'.$value.'" name="jabatan" style="height: 40px; width: 500px;">';});
			

		$crud->unset_print();
		$crud->unset_clone();
		$crud->unset_export();

		$output = $crud->render();
		$this->_example_output($output);
	}

	function format_num($value, $row){
		return 'Rp' .number_format($value, 0,",",".");
		}

	

	////users////
	public function users(){
		$crud = new grocery_CRUD();
		$crud->set_table('users');
		$crud->set_subject('Data User');	
		$crud->required_fields('id','username','password','nama','role');
		
		// $crud->set_field_upload('foto','assets/uploads/images');

		$crud->unset_clone();
		$crud->unset_print();
		$crud->unset_export();

		$crud->change_field_type('password', 'password');
		$crud->callback_before_insert(array($this, 'encrypt_password'));
		$crud->callback_before_update(array($this, 'encrypt_password'));
		
		$output = $crud->render();
		$this->_example_output($output);
	}

	public function encrypt_password($post_array, $primary_key = null){
		$this->load->helper('security');
		$post_array['password'] = do_hash($post_array['password'], 'md5');
		return $post_array;
	}

    public function tes(){
		$lutfi = new grocery_CRUD();
		$lutfi->set_table('wisata');

		$output = $lutfi->render();
		$this->load->view('content_crud_admin',(array)$output);
	}
	////////batas
	public function logout()
	{
	 $this->session->sess_destroy();
	 
	 redirect('login');
	}
  


}
