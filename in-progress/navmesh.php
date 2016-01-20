<?php
/*================================================================================
#
# navmesh.php
#
# This tool takes a binary navmesh and dumps out a JSON file of all the objects.
# It should be considered an intermediate tool, we need a very small subset of
# the output and transform things like coordinates.
================================================================================*/

require_once __DIR__.'/utilities/thirdparty/php-binary/vendor/autoload.php';
include "include/functions.php";
include "include/Spyc.php";

// Parse navmesh file into a JSON output
function ParseNavmesh($navmesh_file) {
	$navmesh_path="/home/insserver/serverfiles/insurgency/maps";
	$parsed_path="data/maps/navmesh.out";
	$schema_yaml="data/thirdparty/schema.navmesh.yaml";
	// File handle for navmesh binary file
	$file = fopen("${navmesh_path}/{$navmesh_file}.nav",'r');

	// Load YAML file manifest of data structure
	$schema_data = UpdateSchemaArray(spyc_load_file($schema_yaml));


	// Schema Builder
	$builder = new Binary\SchemaBuilder;
	// File stream
	$stream = new Binary\Stream\FileStream($file);
	// Schema load
	$schema = $builder->createFromArray($schema_data);
	// Parse file with schema
	$result = $schema->readStream($stream);

	// This is broken. The backreference feature of the binary parser doesn't seem
	// to work, so I am hacking it by running it the first time to collect the
	// values, and replacing them myself with a slow and horrible array recursion.
	// It is incredibly inefficient, but it works (for now).
	// May God have mercy on my soul.

	$schema_data = UpdateSchemaArray($schema_data,$result);
	$schema = $builder->createFromArray($schema_data);
	$result = $schema->readStream($stream);

	// DUMP DAT INFO
	file_put_contents("{$parsed_path}/{$navmesh_file}.json",prettyPrint(json_encode($result)));
}

function UpdateSchemaArray($data,$result=array()) {
	if (isset($data['count'])) {
		if ($data['count'][0] == "@") {
			$key = substr($data['count'], 1);
			if (isset($result[$key])) {
				$data["count"] = $result[$key];
			}
		}
	}
// Don't really need sizes to be loaded. My plan is to merge all the
/*
	$datatypes = new Binary\DataTypes;
	if (isset($data['_type'])) {
		$size = $datatypes->GetSize($data['_type']);
		if ($size)
			$data['size'] = $size;
	}
*/
	foreach ($data as $key => $item) {
		if (is_array($item)) {
			$data[$key] = UpdateSchemaArray($item,$result);
		}
	}
	return $data;
}

// Call navmesh generator for buhriz_coop
ParseNavmesh("buhriz_coop");
