<?php

interface Scalr_Util_Queue {
	
	function put ($data);
	
	function peek ();
	
	function capacity ();
}