<?php
/**
 * appEngine class, appEngine.php
 * Custom Functions for throughout application usage *
 * @version 4.0
 *
 */
namespace Application\Controller\Helper;

class uploadEngine
{
	protected $filesTable;
	protected $systemManager;
	protected $s3Handle;
	public $id=0;
	/**
	 * The name of the file
     * @var FileName
     */
	protected $fileName = NULL;
    /**
	 * The content-type of the file
     * @var ContentType
     */
	protected $contentType;
	/**
	 * The globally-unique filename for CDN use
     * @var CDNName
     */
	protected $cdnName;
	/**
	 * The CDN/StorageDriver on which the file is stored ['S3','LocalFile','etc']
     * @var CDN
     */
	protected $cdn='S3';
	/**
	 * The size of the file in bytes
     * @var Size
     */
	protected $size;
	/**
	 * The content-md5 value of the file (md5 hash base64 encoded)
     * @var ContentMD5
     */
	protected $contentMD5;
	
	/**
	 * This is the filename of the local file for uploading. It must exist for an update to be called.
     * @var FileHandle
     */
	protected $localFile = "";
	/**
	 * This is the bucket on the CDN the file will go in.
     * @var Bucket
     */
	protected $bucket = "";
	protected $isUploaded = false;
	
	/**
	 * Constructor to initialize object
	 *
	 * @param object $sm
	 * @param string $bucket
	 * @return void
	 */
	
	public function __construct( $sm ){
		$this -> systemManager 	= $sm;
		
		$this->s3Handle = \Aws\S3\S3Client::factory(array(
			'key'		=> '#####',
			'secret'	=> '#####',
		));
		
		$schoolSettingContainer = new \Zend\Session\Container('SchoolSettings');
		$bucket = trim($schoolSettingContainer -> s3BucketName);
		$this->bucket 	= $bucket;
	}
	
	/***
	 * Function		  getFileMimeType
	 * @param string  $file
	 * Description	  To get mime-type
	 * @return        return string
	 ***/
	function getFileMimeType($file) {
		require_once dirname(GLOBAL_ABSOLUTE_PATH).'/lib/mime.php';
		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($finfo, $file);
			finfo_close($finfo);
		} else {
			$type = mime_content_type($file);
		}

		if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
			$secondOpinion = exec('file -b --mime-type ' . escapeshellarg($file), $foo, $returnCode);
			if ($returnCode === 0 && $secondOpinion) {
				$type = $secondOpinion;
			}
		}
		if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
			$exifImageType = exif_imagetype($file);
			if ($exifImageType !== false) {
				$type = image_type_to_mime_type($exifImageType);
			}
		}
		return $type;
	}
	/***
	 * Function		  initLocalFile
	 * @param string  $filename
	 * @param string  $cdn
	 * @param string  $bucket
	 * Description	  To initialize class variables
	 * @return        return boolean
	 ***/
	public function initLocalFile( $filename, $cdn="" ){
	
		// filename format is full path from root of server
		////$fileinfo = new \finfo(FILEINFO_MIME, "/usr/share/misc/magic"); 
		if(is_file($filename) && is_readable($filename)){
			$this->isUploaded 	= false;
			$this->localFile 	= $filename;
			$this->contentMD5 	= $this->hex2b64(md5_file($filename));
			////$this->contentType 	= $fileinfo->file($filename);
			$this->contentType 	= $this->getFileMimeType($filename);
			$this->size 		= filesize($filename);
			if( $this->fileName=="" ){
				$this->fileName = basename($filename);
			}
			$cdn="S3";
			//special handling for default bucket
			//$this->bucket="scholar-{$bucket}-storage";
			$this->objectChanged = true;
			return true;
		}else{
			$this->localFile = NULL;
			$this->statCode = "302";
			return false;
		}
	}
	/***
	 * Function		  hex2b64
	 * @param string  $str
	 * Description	  To encrypt string
	 * @return        return string
	 ***/
	private function hex2b64( $str ) { //used to convert the output of md5() to b64 encoded string suitable for content-md5 header
		$raw = '';
		for ($i=0; $i < strlen($str); $i+=2) {
			$raw .= chr(hexdec(substr($str, $i, 2)));
		}
		return base64_encode($raw);
	}
	/***
	 * Function		  setFileName
	 * @param string  $filename
	 * Description	  To set filename
	 * @return        return boolean
	 ***/
	public function setFileName( $filename ){ 
		//That dang LocalFile driver, so many special cases.
		if( !($this->isUploaded && $this->cdn=="LocalFile") ){
			$this->fileName 		= $filename;
			$this->objectChanged 	= true;
		} else {
			throw new \Exception("Cannot rename an already-uploaded file with LocalFile driver");
		}
	}
	
	/***
	 * Function		  update
	 * @param string  $directory
	 * Description	  To add data in files table
	 * @return        return boolean
	 ***/
	function update( $directory = null )
	{
			if($this->id == "0"){
				$tempData = array();
				
				$tempData['fileName'] 		= $this -> fileName;
				$tempData['contentType'] 	= $this -> contentType;
				$tempData['size'] 			= $this -> size;
				$tempData['cdn'] 			= $this -> cdn;
				$tempData['bucket'] 		= $this -> bucket;
				$tempData['contentMD5'] 	= $this -> contentMD5;
				$tempData['status'] 		= '1'  ;
				$tempData['createdBy'] 		= '';
				$tempData['createdDt'] 		= '';
				
				$lastInsertFileId = $this -> getFilesTable() -> saveUpdate($tempData);
				
				if($lastInsertFileId>0){
					$this->id = $lastInsertFileId;
					$this->statCode = "100";
					if( $this->isUploaded == false ){
						try{
							
							if ( $this -> cdn == 'S3') {
								$this -> uploadS3File();
								
							} elseif ( $this -> cdn == 'LocalFile' ) {
								
								
							}							
						}catch (Exception $e){
							//something went wrong with the upload, we need to clean up and rethrow the exception
							//Soft delete already existing data
							$tempData = array();
							$tempData['status'] = '0';
							$tempData['filesId'] = $this->id;
							$this -> getFilesTable() -> saveUpdate($tempData);
							throw $e;
						}
					}
					return true;
					
				} else {
					$this->statCode = "101";
					return false;
				}		
			} else {
				die();
			}
			
	}
	
	/**
	 * Action		readData
	 * Description	To initialize the model
	 *
	 * @param object sm
	 * @return object
	 */
	public function readData( $fileId )
	{
		return $this -> getFilesTable() -> getOne( $fileId );
	}
	
	/***
	 * Function		  getDownladURL
	 * @param integer  $fileId
	 * Description	  To get download url for selected file
	 * @return        return boolean
	 ***/
	public function getDownladURL( $fileId ){
		
		$settings 	= new \Zend\Session\Container('Settings');
		$appUrl 	= $settings -> appUrl;
		$path 		= $appUrl."/repository/filedownload/".$fileId; 
		return $path;
	}
	/**
	 * Action		getFilesTable
	 * Description	To initialize the model
	 *
	 * @param object sm
	 * @return object
	 */
	private function getFilesTable()
	{
		if (!$this -> filesTable) {
			$this -> filesTable = $this -> systemManager -> get('Repository\Model\FilesTable');
		}
		return $this -> filesTable;
	}
	
	//START AWS S3 FUNCTIONS
	
	/***
	 * Function		  uploadS3File
	 * @param string  $dir
	 * Description	  To get upload selected file on S3 server
	 * @return        return boolean
	 ***/
	public function uploadS3File( $dir = null ){
		if( !empty($dir)&&is_string($dir) ){
			$cdnName = appEngine::getS3FileKey( $this->id,$this -> size,$dir ); 
		} else {
			$cdnName = appEngine::getS3FileKey( $this->id,$this -> size ); 
		}
		if ( !($this -> s3Handle -> doesBucketExist( $this -> bucket )) ){
			// Create a valid bucket and use a LocationConstraint
			$result = $this -> s3Handle -> createBucket(array(
				'Bucket'	=> $this -> bucket //Default region is us-east-1
			));
		}
		
		// Upload an object by streaming the contents of a file
		if ( $this -> s3Handle -> doesBucketExist( $this -> bucket ) ){
			$result = $this->s3Handle->putObject(array(
				'Bucket'     => $this->bucket,
				'Key'        => $cdnName,
				'SourceFile' => $this->localFile,
			));
			
			// We can poll the object until it is accessible
			$this -> s3Handle -> waitUntilObjectExists(array(
				'Bucket' => $this->bucket,
				'Key'    => $cdnName
			));
			return true;
		}
		return false;
	}
	
	/***
	 * Function		  echoS3File
	 * @param integer  $filesId
	 * Description	  To get contents of selected file from S3 server
	 * @return        return boolean
	 ***/
	public function echoS3File( $filesId ){
		
		$fileDataObject = $this -> readData( $filesId );
		$startByte = 0; 
		$endByte = 0;
		$fileSize = $fileDataObject -> size;
		$cdnName = appEngine::getS3FileKey( $filesId,$fileSize ); 
		$blockSize = 1048576;
			
		do{
			$endByte = $startByte+$blockSize;
			if($endByte > $fileSize){
				$endByte = $fileSize;
			}
			
			$response = $this -> s3Handle -> getObject(array(
				'Bucket' => $this -> bucket,
				'Key'    => $cdnName,
				'range'=> $startByte."-".$endByte
			));
			echo (string) $response['Body'];
			$startByte = $endByte+1;
		} while($endByte < $fileSize);
	}
	
	/***
	 * Function		  getS3FileContents
	 * @param integer  $filesId
	 * Description	  To get contents of selected file from S3 server
	 * @return        return string
	 ***/
	public function getS3FileContents( $filesId ){
		
		$fileDataObject = $this -> readData( $filesId );
		$startByte = 0; 
		$endByte = 0;
		$fileSize = $fileDataObject -> size;
		$cdnName = appEngine::getS3FileKey( $filesId,$fileSize ); 
		$blockSize = 1048576;
		$fullFile="";
			
		do{
			$endByte = $startByte+$blockSize;
			if($endByte > $fileSize){
				$endByte = $fileSize;
			}
			
			$response = $this -> s3Handle -> getObject(array(
				'Bucket' => $this -> bucket,
				'Key'    => $cdnName,
				'range'=> $startByte."-".$endByte
			));
			$fullFile .= $response['Body'];
			$startByte = $endByte+1;
		} while($endByte < $fileSize);
		return $fullFile;
	}
	
	/***
	 * Function		  deleteS3File
	 * @param integer  $filesId
	 * Description	  To delete selected file on S3 server and update status in files table
	 * @return        return boolean
	 ***/
	public function deleteS3File( $filesId ){
		//Get files data
		$fileDataObject = $this -> readData( $filesId );
		if ( $fileDataObject->filesId>0 ) {
			$cdnName = appEngine::getS3FileKey( $filesId,$fileDataObject->size );
			//Move deleted object into "e360_vteams_delete" bucket starts 
			try {
				$this -> s3Handle -> copyObject(array(
					'Bucket'		=> 'e360_vteams_delete',
					'Key'			=> $cdnName,
					'CopySource'	=> $this -> bucket."/".$cdnName,
				));
			} catch (\Aws\S3\Exception\NoSuchBucketException $e) {
				return false;
			} catch (\Aws\S3\Exception\NoSuchKeyException $e) {
				return false;
			}	
			//Move deleted object into "e360_vteams_delete" bucket ends
			
			//Delete from S3 bucket
			try {
				$this -> s3Handle->deleteObject(array(
					'Bucket' 	=> $this -> bucket,
					'Key' 		=> $cdnName
				));	
			} catch (\Aws\S3\Exception\NoSuchBucketException $e) {
				return false;
			} catch (\Aws\S3\Exception\NoSuchKeyException $e) {
				return false;
			}			
			//Update status in files table starts
			$tempArray = array();
			$tempArray['filesId'] 	= $filesId;
			$tempArray['status'] 	= '-1';
			$this -> getFilesTable() -> saveUpdate( $tempArray );
			//Update status in files table ends
			return true;
		}
		return false;
	}
	
	/***
	 * Function		  makeObjectPublic
	 * @param integer  $filename
	 * Description	  To make object public
	 * @return        return boolean
	 ***/
	public function makeObjectPublic( $fileName ){
		try {
			$result = $this -> s3Handle -> putObjectAcl(array(
				'ACL' 		=> 'public-read',
				'Bucket' 	=> $this->bucket,
				'Key' 		=> $fileName,
			));	
		} catch (\Aws\S3\Exception\NoSuchBucketException $e) {
			return false;
		} catch (\Aws\S3\Exception\NoSuchKeyException $e) {
			return false;
		}	
		return true;
	}
	//ENDS AWS S3 FUNCTIONS
}
?>