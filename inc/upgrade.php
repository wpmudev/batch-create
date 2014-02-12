<?php

function batch_create_upgrade_14() {
	$model = batch_create_get_model();
	$model->create_schema();
}