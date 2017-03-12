<?php

/**
 * @property CI_Loader $load Description
 * @property Remotedb $rdb Description
 */
class Langitan_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
		
		$this->load->library('remotedb', NULL, 'rdb');
		$this->rdb->set_url($this->session->userdata('langitan'));
	}
	
	function list_semester($npsn)
	{
		return $this->rdb->QueryToArray(
			"SELECT
				id_semester,
				thn_akademik_semester||CASE upper(nm_semester) WHEN 'GANJIL' THEN '1' WHEN 'GENAP' THEN '2' WHEN 'PENDEK' THEN '3' END AS id_smt,
				tahun_ajaran, nm_semester
			FROM semester s
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
			WHERE pt.npsn = '{$npsn}'
			ORDER BY thn_akademik_semester desc, nm_semester desc");
	}
	
	function get_semester($id_semester)
	{
		$semester_set = $this->rdb->QueryToArray(
			"SELECT
				id_semester,
				thn_akademik_semester||CASE upper(nm_semester) WHEN 'GANJIL' THEN '1' WHEN 'GENAP' THEN '2' WHEN 'PENDEK' THEN '3' END AS id_smt,
				tahun_ajaran, nm_semester
			FROM semester s
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
			WHERE s.id_semester = {$id_semester}
			ORDER BY thn_akademik_semester desc, nm_semester desc");
		return $semester_set[0];
	}
	
	function get_id_semester($npsn, $id_smt)
	{
		$semester_set = $this->rdb->QueryToArray(
			"SELECT id_semester FROM semester s
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
			WHERE pt.npsn = '{$npsn}' AND thn_akademik_semester||decode(nm_semester, 'Ganjil',1,'Genap',2,'Pendek',3) = '{$id_smt}'");
		return $semester_set[0]['ID_SEMESTER'];
	}
	
	function get_semester_langitan($npsn, $id_smt)
	{
		$semester_set = $this->rdb->QueryToArray(
			"SELECT tahun_ajaran||' '||nm_semester as semester_langitan FROM semester s
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
			WHERE 
				pt.npsn = '{$npsn}' AND 
				s.thn_akademik_semester||
				decode(s.nm_semester,'Ganjil',1,'Genap',2,'Pendek',3) = '{$id_smt}'");
		return $semester_set[0]['SEMESTER_LANGITAN'];
	}
	
	function list_program_studi($npsn)
	{
		return $this->rdb->QueryToArray(
			"SELECT id_program_studi, nm_jenjang, nm_program_studi, kode_program_studi FROM program_studi ps
			JOIN jenjang j on j.id_jenjang = ps.id_jenjang
			JOIN fakultas f on f.id_fakultas = ps.id_fakultas
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
			WHERE pt.npsn = '{$npsn}' ORDER BY nm_jenjang, nm_program_studi");
	}
}
