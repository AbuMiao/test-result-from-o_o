<?php
namespace core\tools;
use Flight;

class ClsImageTools
{
	static private $image_floder = "media/images/";
	static public function setRoute(){
        Flight::route(
            'POST /image',
            array('Tools\\ClsImageTools', 'cbGenerateImage')
        );
        Flight::route(
            'GET /image/@image_id',
            array('Tools\\ClsImageTools', 'cbGetImg')
        );
    }

    static public function cbGetImg($image_id){
    	// check if image file exists
    	$img_file_path = ClsImageTools::$image_floder . $image_id.'.png';
    	if(!file_exists($img_file_path)){
    		Flight::sendRouteResult(false, null, "img not exists");
    	}

    	// create image
	    $image = @imagecreatefrompng($img_file_path);
	    if(!$image)
	    {
	    	//create image for error notification
	        /* Create a blank image */
	        $image  = imagecreatetruecolor(150, 30);
	        $bgc = imagecolorallocate($image, 255, 255, 255);
	        $tc  = imagecolorallocate($image, 0, 0, 0);

	        imagefilledrectangle($image, 0, 0, 150, 30, $bgc);

	        /* Output an error message */
	        imagestring($image, 1, 5, 5, 'Error loading ' . $image_id, $tc);
	    }else{
			imagealphablending($image, false); 
			imagesavealpha($image,true); 
	    }

	    // show image
		header('Content-Type: image/png');
		imagepng($image);

		// destroy image for memory release
		imagedestroy($image);
		die();
    }
    
    static public function cbGenerateImage(){
    	// check image data
		$data = Flight::request()->data;
		$success = false;
		$result_data = null;
		$message = "";
		if(!isset($data['image_data']) || !is_string($data['image_data'])){
			$success = false;
			$message = '图像数据缺失或格式异常';
		}else{
			$image_id = ClsImageTools::generateImage($data['image_data']);
			if($image_id){
				$success = true;
				$result_data = array("image_id"=>$image_id, "image_url"=>ClsImageTools::getImgUrl($image_id));
			}
		    else{
		    	$success = false;
		    	$message = "图片生成失败";
		    }		
		}
    	Flight::sendRouteResult($success, $result_data, $message);
    }

    static public function generateImage($image_data){
		// decode
		$image_data = base64_decode($image_data);

		// create image
		$image = @imagecreatefromstring($image_data);
		if ($image !== false) {
			// generate image_id
			$image_id = md5($image_data);

			// process image for alpha
			imagealphablending($image, false);
			imagesavealpha($image, true);
			//imagescale($image, 100, 100); 需要5.5以上版本支持

			// save image
			imagepng($image, ClsImageTools::$image_floder . $image_id.".png");

			// destroy image for memory release
		    imagedestroy($image);

		    return $image_id;
		}
		else {
			return false;
		}
    }
    static public function getImgUrl($image_id){
    	return "https://" . $_SERVER['SERVER_NAME']. "/image/" . $image_id;
    }
}

ClsImageTools::setRoute();

?>