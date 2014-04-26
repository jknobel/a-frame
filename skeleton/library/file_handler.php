<?
class file_handler extends base
{

	public function file_extension($file_object)
	{
		$ext = substr($file_object["name"], strrpos($file_object["name"], '.'));
		return $ext;
	}	

	public function generateRandomFilename($extension='', $length=10)
	{
		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$filename = '';
		for($i = 0; $i < $length; $i++)
		{
		    $filename .= $chars[mt_rand(0, 35)];
		}

		if($extension)
			$filename .= '.'.$extension;

		return $filename;
	}

	public function human_filesize($bytes, $decimals = 2) {
	  $sz = 'BKMGTP';
	  $factor = floor((strlen($bytes) - 1) / 3);
	  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

}