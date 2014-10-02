<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 CoderGarden
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class SlugHelper {

	/* 
	 * Separator between words in slug. 
	 * Dấu phân cách cho các từ trong slug.
	 */
	private $separator = "-";
	/* 
	 * Max length for slug in order to guarantee our slug' length 
	 * is not over slug field's length in database. 
	 * Giới hạn độ dài cho slug nhằm tránh vấn đề độ dài slug 
	 * vượt quá độ dài quy định trong cơ sở dữ liệu.
	*/
    	private $max_length = null;
	/* 
	 * Use cache for holding slug indexing. 
	 * Sử dụng bộ nhớ đệm để quản lý số lượng các slug có tên giống nhau.
	 */
    	private $use_cache = false;
	private $cache_key = "SLUG_KEY";
	
	/*
	 * The related model (table in DB) which holds slug fields.
	 * 
	 * Model (bảng dữ liệu trong DB) có lưu trữ slug
	 */
    	public $model = YOUR_TABLE_NAME;
	/*
	 * The related id field of model.
	 * 
	 * Tên của trường id (định danh cho các bản ghi) của model.
	 */
    	public $id_field = "id";
	/*
	 * The related slug field of model.
	 * 
	 * Tên của trường slug (trường lưu trữ slug) của model.
	 */
    	public $slug_field = "slug";
    	/*
	 * Id of existing model.
	 * 
	 * Id của bản ghi đang lưu trữ slug.
	 */
    	public $id = false;

	/*
	 * Vietname with tone marks characters.
	 * 
	 * Các từ tiếng Việt có dấu.
	 */
    	private $vnmToneChars=array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ","ặ","ẳ","ẵ",
                                "è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ",
                                "ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ","ờ","ớ","ợ","ở","ỡ",
                                "ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ",
                                "ỳ","ý","ỵ","ỷ","ỹ",
                                "đ",
                                "À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă"
                                ,"Ằ","Ắ","Ặ","Ẳ","Ẵ",
                                "È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ",
                                "Ì","Í","Ị","Ỉ","Ĩ",
                                "Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ"
                                ,"Ờ","Ớ","Ợ","Ở","Ỡ",
                                "Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ",
                                "Ỳ","Ý","Ỵ","Ỷ","Ỹ",
                                "Đ","ê","ù","à");
	/*
	 * Vietname without tone marks characters.
	 * 
	 * Các từ tiếng Việt không dấu.
	 */
    	private $vnmNoTone=array("a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a",
                                "e","e","e","e","e","e","e","e","e","e","e",
                                "i","i","i","i","i",
                                "o","o","o","o","o","o","o","o","o","o","o","o"
                                ,"o","o","o","o","o",
                                "u","u","u","u","u","u","u","u","u","u","u",
                                "y","y","y","y","y",
                                "d",
                                "A","A","A","A","A","A","A","A","A","A","A","A"
                                ,"A","A","A","A","A",
                                "E","E","E","E","E","E","E","E","E","E","E",
                                "I","I","I","I","I",
                                "O","O","O","O","O","O","O","O","O","O","O","O"
                                ,"O","O","O","O","O",
                                "U","U","U","U","U","U","U","U","U","U","U",
                                "Y","Y","Y","Y","Y",
                                "D","e","u","a");

    	public function __construct($separator = "_", $max_length = null, $use_cache = false, $cache_key = "SLUG_KEY") {
		$this->separator = $separator;
		$this->max_length = $max_length;
		$this->use_cache = $use_cache;
		$this->cache_key = $cache_key;
    	}

	/**
	 * Except all Vietnamese tone marks by 
	 * replacing the relative English words.
	 * 
	 * Loại bỏ dấu cho tiếng Việt.
	 *
	 * (c) 2014 CoderGarden
	 */
    	private function exceptVnmToneMarks($source) {
        	return str_replace($this->vnmToneChars, $this->vnmNoTone, $source);
    	}

	/**
	 * Generate an unique Slug.
	 *
	 * Sinh một Slug duy nhất.
	 *
	 * (c) 2014 CoderGarden
	 */
    	private function generateSlug($source) {
        	$slug = Str::slug($source, $this->separator);
        	if ($this->max_length) {
            		$slug = substr($slug, 0, $this->max_length);
        	}
		/* 
		 * Make slug unique.
		 * Tạo mã duy nhất cho Slug
		 */
		$counter = $this->getSlugCounter($slug);
		if ($slug_counter > 0) {
			$slug .= $this->separator . $slug_counter;
		}
		
        	return $slug;
    	}

	/**
	 * Get number of slug with the same name.
	 *
	 * Tìm số Slug có trùng tên.
	 *
	 * Note: This code is implemented with Laravel.
	 * You can change this for your specific environment.
	 *
	 * Chú ý: Đoạn mã này thực hiện với Laravel.
	 * Bạn có thể tự chỉnh sửa đoạn này cho phù hợp.
	 *
	 * (c) 2014 CoderGarden
	 */
    	private function getSlugCounter ($slug) {
		$slug_counter = 0;
        	if ($this->use_cache) {
        		/*
        		 * Find counter of slug in the Cache
        		 * 
        		 * Lấy ra số lượng slug cùng tên được lưu trữ trong bộ nhớ đệm (Cache)
        		 */
            		$slug_counter = Cache::tags($this->cache_key)->get($slug);
            		if ($slug_counter !== null) {
				$slug_counter ++;
            		}
			Cache::tags($this->cache_key)->put($slug, $slug_counter, $use_cache);
        	} else {
        		/*
        		 * Find all records which contains current slug
        		 * 
        		 * Lấy toàn bộ bản ghi có chưa slug đang cần tìm
        		 */
			$list = DB::table($this->model)->where($this->slug_field, 'LIKE', $slug.'%')
					->lists($this->slug_field, $this->id_field);
			/*
			 * Find the maximum counter in list of records.
			 *
			 * Lấy ra mã gắn lớn nhất cho slug
			 */
			if (!empty($list) && in_array($slug, $list) &&
				(!$this->id ||
				!array_key_exists($this->id, $list) || $list[$this->id] !== $slug)) {
				$len = strlen($slug . $this->separator);
				foreach ($list as $slug_index) {
					$slug_index = intval(substr($slug_index, $len));
					if ($slug_index > $slug_counter) {
						$slug_counter = $slug_index;
					}
				}
				$slug_counter ++;
			}
		}
		
        	return $slug_counter;
    	}

	/**
	 * Generate Slug for Vietnamese and English.
	 *
	 * Tạo Slug cho tiếng Việt và tiếng Anh.
	 * 
	 * (c) 2014 CoderGarden
	 */
    	public function sluggify($source) {
        	// except vietnamese tone marks
        	$source  = $this->exceptVnmToneMarks($source);
        	// convert into slug
        	$slug = $this->generateSlug($source);

        	return $slug;
    	}

}
