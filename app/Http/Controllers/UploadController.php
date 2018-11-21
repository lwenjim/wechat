<?php

namespace App\Http\Controllers;

use OSS\OssClient;
use OSS\Core\OssException;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UploadController extends Controller
{
    protected $ossClient;

    public function index()
    {
        $catalog = $this->request->get('catalog', 'mornight');
        $filename = $this->request->get('filename', 0);
        $data = $this->request->input('data');
        $res = $this->upload($catalog, $filename);
        return ['src' => $res['info']['url'], 'data' => $data];
    }

    public function upload($catalog, $filename)
    {
        try {
            if ($this->request->isMethod('post')) {
                $file = $this->request->file('image');
                $res = $this->getOssClient()->uploadFile(config('filesystems.disks.aliyun.bucket'), $catalog . '/' . date('Y/m/d') . '/' . ($filename ? $file->getClientOriginalName() : $file->hashName()), $file->path());
            } else {
                $content = $this->request->input('content');
                $res = $this->getOssClient()->putObject(config('filesystems.disks.aliyun.bucket'), $catalog . '/' . date('Y/m/d') . '/' . ($filename ? $filename : md5($content)) . '.jpg', base64_decode($content));
            }
            return $res;
        } catch (OssException $e) {
            return $this->upload($catalog, $filename);
        }
    }

    public function ueditor()
    {
        $config = $this->getConfig();
        $action = $this->request->get('action');
        if ($action == 'config') {
            $result = $config;
        } else {
            $config = $this->getUploadConfig($action);
            if (!$this->request->hasFile($config['field_name'])) {
                return ['state' => trans("upload.UPLOAD_ERR_NO_FILE")];
            }
            $file = $this->request->file($config['field_name']);
            if (!$file->isValid()) {
                return ['state' => trans("upload.{$file->getError()}")];
            } elseif ($file->getSize() > $config['max_size']) {
                return ['state' => trans("upload.ERROR_SIZE_EXCEED")];
            } elseif (!empty($config['allow_files']) && !in_array('.' . $file->guessExtension(), $config['allow_files'])) {
                return ['state' => trans("upload.ERROR_TYPE_NOT_ALLOWED")];
            }
            $upload = function ($filename, $file) use (&$upload) {
                try {
                    $res = $this->getOssClient()->uploadFile(config('filesystems.disks.aliyun.bucket'), $filename, $file->path());
                    return [
                        'state' => 'SUCCESS',
                        'url' => $res['info']['url'],
                        'title' => $filename,
                        'original' => $file->getClientOriginalName(),
                        'type' => $file->getExtension(),
                        'size' => $file->getSize(),
                    ];
                } catch (OssException $e) {
                    return $upload($filename, $file);
                }
            };
            $filename = $this->getFilename($file, $config);
            $result = $upload($filename, $file);
        }
        return $result;
    }

    /**
     * Get the new filename of file.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param array $config
     *
     * @return string
     */
    protected function getFilename($file, array $config)
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $config["path_format"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);
        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($file->getClientOriginalName(), 0, strrpos($file->getClientOriginalName(), '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);
        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }
        return $format . '.' . $file->guessExtension();
    }

    /**
     * Get configuration of current action.
     *
     * @param string $action
     *
     * @return array
     */
    protected function getUploadConfig($action)
    {
        $upload = $this->getConfig();
        $prefixes = [
            'image', 'scrawl', 'snapscreen', 'catcher', 'video', 'file',
            'imageManager', 'fileManager',
        ];
        $config = [];
        foreach ($prefixes as $prefix) {
            if ($action == $upload[$prefix . 'ActionName']) {
                $config = [
                    'action' => array_get($upload, $prefix . 'ActionName'),
                    'field_name' => array_get($upload, $prefix . 'FieldName'),
                    'max_size' => array_get($upload, $prefix . 'MaxSize'),
                    'allow_files' => array_get($upload, $prefix . 'AllowFiles', []),
                    'path_format' => array_get($upload, $prefix . 'PathFormat'),
                ];
                break;
            }
        }
        return $config;
    }

    protected function getConfig()
    {
        /* 前后端通信相关的配置,注释只允许使用多行方式 */
        return [
            "imageActionName" => "uploadimage", /* 执行上传图片的action名称 */
            "imageFieldName" => "upfile", /* 提交的图片表单名称 */
            "imageMaxSize" => 2048000, /* 上传大小限制，单位B */
            "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 上传图片格式显示 */
            "imageCompressEnable" => true, /* 是否压缩图片,默认是true */
            "imageCompressBorder" => 1600, /* 图片压缩最长边限制 */
            "imageInsertAlign" => "none", /* 插入的图片浮动方式 */
            "imageUrlPrefix" => "", /* 图片访问路径前缀 */
            "imagePathFormat" => "ueditor/image/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            /* {filename} 会替换成原文件名,配置这项需要注意中文乱码问题 */
            /* {rand:6} 会替换成随机数,后面的数字是随机数的位数 */
            /* {time} 会替换成时间戳 */
            /* {yyyy} 会替换成四位年份 */
            /* {yy} 会替换成两位年份 */
            /* {mm} 会替换成两位月份 */
            /* {dd} 会替换成两位日期 */
            /* {hh} 会替换成两位小时 */
            /* {ii} 会替换成两位分钟 */
            /* {ss} 会替换成两位秒 */
            /* 非法字符 \ : * ? " < > | */
            /* 具请体看线上文档: fex.baidu.com/ueditor/#use-format_upload_filename */

            /* 涂鸦图片上传配置项 */
            "scrawlActionName" => "uploadscrawl", /* 执行上传涂鸦的action名称 */
            "scrawlFieldName" => "upfile", /* 提交的图片表单名称 */
            "scrawlPathFormat" => "ueditor/image/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "scrawlMaxSize" => 2048000, /* 上传大小限制，单位B */
            "scrawlUrlPrefix" => "", /* 图片访问路径前缀 */
            "scrawlInsertAlign" => "none",

            /* 截图工具上传 */
            "snapscreenActionName" => "uploadimage", /* 执行上传截图的action名称 */
            "snapscreenPathFormat" => "ueditor/image/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "snapscreenUrlPrefix" => "", /* 图片访问路径前缀 */
            "snapscreenInsertAlign" => "none", /* 插入的图片浮动方式 */

            /* 抓取远程图片配置 */
            "catcherLocalDomain" => ["127.0.0.1", "localhost", "img.baidu.com"],
            "catcherActionName" => "catchimage", /* 执行抓取远程图片的action名称 */
            "catcherFieldName" => "source", /* 提交的图片列表表单名称 */
            "catcherPathFormat" => "ueditor/image/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "catcherUrlPrefix" => "", /* 图片访问路径前缀 */
            "catcherMaxSize" => 2048000, /* 上传大小限制，单位B */
            "catcherAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 抓取图片格式显示 */

            /* 上传视频配置 */
            "videoActionName" => "uploadvideo", /* 执行上传视频的action名称 */
            "videoFieldName" => "upfile", /* 提交的视频表单名称 */
            "videoPathFormat" => "ueditor/video/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "videoUrlPrefix" => "", /* 视频访问路径前缀 */
            "videoMaxSize" => 102400000, /* 上传大小限制，单位B，默认100MB */
            "videoAllowFiles" => [
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"], /* 上传视频格式显示 */

            /* 上传文件配置 */
            "fileActionName" => "uploadfile", /* controller里,执行上传视频的action名称 */
            "fileFieldName" => "upfile", /* 提交的文件表单名称 */
            "filePathFormat" => "ueditor/file/{yyyy}/{mm}/{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "fileUrlPrefix" => "", /* 文件访问路径前缀 */
            "fileMaxSize" => 51200000, /* 上传大小限制，单位B，默认50MB */
            "fileAllowFiles" => [
                ".png", ".jpg", ".jpeg", ".gif", ".bmp",
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
                ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
                ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
            ], /* 上传文件格式显示 */

            /* 列出指定目录下的图片 */
            "imageManagerActionName" => "listimage", /* 执行图片管理的action名称 */
            "imageManagerListPath" => "ueditor/image/", /* 指定要列出图片的目录 */
            "imageManagerListSize" => 20, /* 每次列出文件数量 */
            "imageManagerUrlPrefix" => "", /* 图片访问路径前缀 */
            "imageManagerInsertAlign" => "none", /* 插入的图片浮动方式 */
            "imageManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 列出的文件类型 */

            /* 列出指定目录下的文件 */
            "fileManagerActionName" => "listfile", /* 执行文件管理的action名称 */
            "fileManagerListPath" => "ueditor/file/", /* 指定要列出文件的目录 */
            "fileManagerUrlPrefix" => "", /* 文件访问路径前缀 */
            "fileManagerListSize" => 20, /* 每次列出文件数量 */
            "fileManagerAllowFiles" => [
                ".png", ".jpg", ".jpeg", ".gif", ".bmp",
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
                ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
                ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
            ]
        ];
    }

    protected function getOssClient()
    {
        try {
            return $this->ossClient = new OssClient(config('filesystems.disks.aliyun.accessKeyId'), config('filesystems.disks.aliyun.accessKeySecret'), config('filesystems.disks.aliyun.endpoint'), true);
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }
}