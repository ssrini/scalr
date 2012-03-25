<?php

interface Scalr_Messaging_Service_QueueHandler {
	
	function accept($queue);
	
	function handle($queue, Scalr_Messaging_Msg $message, $rawMessage);
	
}