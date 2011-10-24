<?php
//source http://www.electrictoolbox.com/php-generate-thumbnails/

class thumbnailGenerator {

    var $allowableTypes = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG
    );

    public function imageCreateFromFile($filename, $imageType) {

        switch($imageType) {
            case IMAGETYPE_GIF  : return imagecreatefromgif($filename);
            case IMAGETYPE_JPEG : return imagecreatefromjpeg($filename);
            case IMAGETYPE_PNG  : return imagecreatefrompng($filename);
            default             : 
                elog("unknown image type $imageType");
                return false;
        }

    }

    /**
     * Generates a thumbnail image using the file at $sourceFilename and either writing it
     * out to a new file or directly to the browser.
     *
     * @param string $sourceFilename Filename for the image to have thumbnail made from
     * @param integer $maxWidth The maxium width for the resulting thumbnail
     * @param integer $maxHeight The maxium height for the resulting thumbnail
     * @param string $targetFormatOrFilename Either a filename extension (gif|jpg|png) or the
     *   filename the resulting file should be written to. This is optional and if not specified
     *   will send a jpg to the browser.
     * @return boolean true if the image could be created, false if not
     */
    public function generate($sourceFilename, $maxWidth, $maxHeight) {

        $size = getimagesize($sourceFilename); // 0 = width, 1 = height, 2 = type

        // check to make sure source image is in allowable format
        if(!in_array($size[2], $this->allowableTypes)) {
            return false;
        }

        // load the image and return false if didn't work
        $source = $this->imageCreateFromFile($sourceFilename, $size[2]);
        if(!$source) {
            return false;
        }
        header("Content-Type: image/jpeg");

        // if the source fits within the maximum then no need to resize
        if($size[0] <= $maxWidth && $size[1] <= $maxHeight) {
            imagejpeg($source);
        }
        else {
            $ratioWidth = $maxWidth / $size[0];
            $ratioHeight = $maxHeight / $size[1];

            // use smallest ratio
            if($ratioWidth < $ratioHeight) {
                $newWidth = $maxWidth;
                $newHeight = round($size[1] * $ratioWidth);
            }
            else {
                $newWidth = round($size[0] * $ratioHeight);
                $newHeight = $maxHeight;
            }

            $target = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $size[0], $size[1]);
            imagejpeg($target);
        }

        return true;

    }
}
