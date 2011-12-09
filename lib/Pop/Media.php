<?php

Class Pop_Media 
{
	public static $media_types = array(
		'application/pdf',
		'application/json',
		'application/atom+xml',
		'application/vnd.google-earth.kml+xml',
		'audio/*',
		'image/*',
		'text/*',
		'video/*',
	);

	/** 
	 * from php port of Mimeparse
	 * Python code (http://code.google.com/p/mimeparse/)
	 * @author Joe Gregario, Andrew "Venom" K.
	 *
	 * patched (changed split to explode) by Patrick Hochstenbach
	 */
	public static function parseMimeType($mime_type)
	{
		$parts = explode(";", $mime_type);
		$params = array();
		foreach ($parts as $i=>$param) {
			if (strpos($param, '=') !== false) {
				list ($k, $v) = explode('=', trim($param));
				$params[$k] = $v;
			}
		}
		list ($type, $subtype) = explode('/', $parts[0]);
		if (!$subtype) throw new Exception("malformed mime type");
		return array(trim($type), trim($subtype), $params);
	}

	/** returns type on success, false on failure */
	public static function isAcceptable($content_type)
	{
		$ok_type = false;
		try {
			list($type,$subtype) = Pop_Media::parseMimeType($content_type);
		} catch (Exception $e) {
			return false;
		}
		foreach(self::$media_types as $t) {
			list($acceptedType,$acceptedSubtype) = explode('/',$t);
			if($acceptedType == '*' || $acceptedType == $type) {
				if($acceptedSubtype == '*' || $acceptedSubtype == $subtype)
					$ok_type = $type . "/" . $subtype;
			}
		}
		return $ok_type;
	}
}
