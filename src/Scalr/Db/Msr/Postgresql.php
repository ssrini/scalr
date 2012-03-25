<?php
class Scalr_Db_Msr_Postgresql extends Scalr_Db_Msr
{
	/* DBFarmRole settings */
	
	const ROOT_PASSWORD = 'db.msr.postgree.root_password';
	const ROOT_USERNAME = 'db.msr.postgree.root_username';
	const ROOT_SSH_PRIV_KEY = 'db.msr.postgree.root_ssh_private_key';
	const ROOT_SSH_PUB_KEY  = 'db.msr.postgree.root_ssh_public_key';
	const XLOG_LOCATION	= 'db.msr.postgree.xlog_location';
}