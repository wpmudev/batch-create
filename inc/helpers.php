<?php

function batch_create_get_model() {
	return Incsub_Batch_Create_Model::get_instance();
}

function batch_create_get_creator() {
	return Incsub_Batch_Create::$batch_creator;
}

/**
 * Uses base64 encoding to mangle the string before regularly urlencoding it.
 *
 * This is a fix for redirection 404 on some servers.
 *
 * @param string Message to be encoded.
 * @return string encoded message.
 */
function batch_create_urlencode ($str) {
	return urlencode(base64_encode($str));
}

/**
 * Decodes the string back to its' original, usable form.
 *
 * @param string Message processed with batch_urlencode().
 * @return string Decoded message.
 */
function batch_create_urldecode ($str) {
	return urldecode(base64_decode($str));
}


