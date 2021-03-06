<?php
/* ImageController.php --- 
 * 
 * Filename: ImageController.php
 * Description: 
 * Author: Gu Weigang  * Maintainer: 
 * Created: Thu Feb 13 15:21:41 2014 (+0800)
 * Version: master
 * Last-Updated: Mon Mar 24 23:13:13 2014 (+0800)
 *           By: Gu Weigang
 *     Update #: 93
 * 
 */

/* Change Log:
 * 
 * 
 */

/* This program is part of "Baidu Darwin PHP Software"; you can redistribute it and/or
 * modify it under the terms of the Baidu General Private License as
 * published by Baidu Campus.
 * 
 * You should have received a copy of the Baidu General Private License
 * along with this program; see the file COPYING. If not, write to
 * the Baidu Campus NO.10 Shangdi 10th Street Haidian District, Beijing The People's
 * Republic of China, 100085.
 */

/* Code: */

namespace BullSoft\Sample\Controllers;

use Imagine\Image\Box;
use Imagine\Image\Point;
use BullSoft\Sample\Models\Image as ImageModel;

class ImageController extends ControllerBase
{
    public function upload($imageUrls)
    {
        $conf = $this->getDI()->get('config');
        $bcs = new \BaiduBCS($conf->bcs->ak, $conf->bcs->sk, $conf->bcs->host);
        foreach($imageUrls as $objName => $fileUpload) {
            $response = $bcs->create_object($conf->bcs->bucket,
                                            $objName,
                                            $fileUpload,
                                            array('acl' => \BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_READ));
            if (! $response->isOK ()) {
                foreach($imageUrls as $objName => $fileUpload) {
                    $bcs->delete_object($conf->bcs->bucket, $objName);
                }
                return false;
            }
        }
        return true;
    }

    public function getAction($objName)
    {
        $conf = $this->getDI()->get('config');
        $bcs = new \BaiduBCS($conf->bcs->ak, $conf->bcs->sk, $conf->bcs->host);
        $objName = "/3EBF946E-A756-099E-4B2E-89A0153D19AF_300.jpg";
        $response = $bcs->get_object($conf->bcs->bucket, $objName);

        if (! $response->isOK ()) {
            $this->flashJson("抱兼，文件获取失败，请重试！");
        } else {
            header("Content-type: image/jpeg");
            echo $response->body;
        }
        exit;
    }

    public function resizeAction()
    {
        $conf = $this->getDI()->get('config');
        $tmpDir = $conf->application->tmpDir;
        $tmpImgDir = $conf->application->tmpDir."images/";
        if(!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        if(!is_dir($tmpImgDir)) {
            mkdir($tmpImgDir, 0777, true);
        }
        
        $imgUrl = $this->request->getPost('img_url');
        $productUrl = $this->request->getPost('product_from_url');

        if(empty($imgUrl) || empty($productUrl)) {
            $this->flashJson(500, array(), "非法请求");
            exit ;
        }
        
        // $imgUrl = 'http://img11.360buyimg.com/n1/g14/M00/10/1F/rBEhVlMDOEMIAAAAAASiVlD7TXwAAIz3gAp0kUABKJu998.jpg';
        // $productUrl = 'http://item.jd.com/1082070511.html';

        $imagine = new \Imagine\Gd\Imagine();
        $image   = $imagine->open($imgUrl);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $filename = $this->createGuid('image');
        $extname = pathinfo($imgUrl, PATHINFO_EXTENSION);
        
        $imageUrls = array();
        foreach(array(0, 75, 150, 300) as $width) {
            $objName = '/'.$filename.'_'.$width.'.'.$extname;
            $filePath = $tmpImgDir.$objName;
            if($width == 0) {
                $image->save($filePath);
            } else {
                $size = new \Imagine\Image\Box($width, $width);
                $image->thumbnail($size, $mode)->save($filePath, array('quality' => 100));
            }
            $imageUrls[$objName] = $filePath;
        }
        
        if($this->upload($imageUrls)) {
            $imageModel = new ImageModel();
            $imageModel->product_from_url = $productUrl;
            $imageModel->name = $filename;
            $imageModel->extname = $extname;
            $imageModel->url_prefix = 'http://bcs.duapp.com/'.$conf->bcs->bucket.'/';
            if($imageModel->save() == false) {
                foreach($imageModel->getMessages() as $message) {
                    echo $message->__toString();
                }
            }
            $this->flashJson(200, array('filename'=> $filename));
        } else {
            $this->flashJson(500, array(), "抱歉，文件上传失败，请重试！");
        }
        exit;
    }

    public function createGuid($namespace = '')
    {
        $guid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['SERVER_ADDR'];
        $data .= $_SERVER['SERVER_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid =
            substr($hash,  0,  8) . 
            '-' .
            substr($hash,  8,  4) .
            '-' .
            substr($hash, 12,  4) .
            '-' .
            substr($hash, 16,  4) .
            '-' .
            substr($hash, 20, 12);
        return $guid;
    }    
}

/* ImageController.php ends here */