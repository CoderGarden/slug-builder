<?php
class SlugHelper {

	/* 
	 * Separator between words in slug. 
	 * Dấu phân cách cho các từ trong slug.
	*/
	private $separator = "-";
	/* 
	 * Max length for slug in order to guarantee our slug' length is not over slug field's length in database. 
	 * Giới hạn độ dài cho slug nhằm tránh vấn đề độ dài slug vượt quá độ dài quy định trong cơ sở dữ liệu.
	*/
    private $max_length = null;
	/* 
	 * Use cache for holding slug indexing. 
	 * Sử dụng bộ nhớ đệm để quản lý số lượng các slug có tên giống nhau.
	*/
    private $use_cache = false;
	
	
    public $model = YOUR_TABLE_NAME;
    public $id_field = "id";
    public $slug_field = "slug";
    public $id = false;

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

    public function __construct($separator = "_", $max_length = null, $use_cache = false) {
		$this->separator = $separator;
		$this->max_length = $max_length;
		$this->use_cache = $use_cache;
    }

    private function exceptVnmToneMarks($source) {
        return str_replace($this->vnmToneChars, $this->vnmNoTone, $source);
    }

    private function generateSlug($source) {
        $slug = Str::slug($source, $this->separator);
        if ($this->max_length) {
            $slug = substr($slug, 0, $this->max_length);
        }
        return $slug;
    }

    private function makeSlugUnique($slug) {
        if ($this->use_cache) {
            $increment = Cache::tags('sluggable')->get($slug);
            if ( $increment === null ) {
                Cache::tags('sluggable')->put($slug, 0, $use_cache);
            } else {
                Cache::tags('sluggable')->put($slug, ++$increment, $use_cache);
                $slug .= $this->separator . $increment;
            }
            return $slug;
        }
		
        $list = $this->getExistingSlugs($slug);
        if (empty($list) || !in_array($slug, $list) ||
            ($this->id && array_key_exists($this->id, $list) && $list[$this->id] === $slug)) {
            return $slug;
        }
		
		$slug .= $this->separator;
        $len = strlen($slug);
        array_walk($list, function(&$value, $key) use ($len) {
            $value = intval(substr($value, $len));
        });
        rsort($list);
        $increment = reset($list) + 1;

        return $slug . $increment;
    }

	/**
	 * Get list of existing slugs in your DB.
	 * This code is implemented with Laravel.
	 * You can change this for your specific environment.
	 */
    private function getExistingSlugs($slug) {
        $query = DB::table($this->model)->where($this->slug_field, 'LIKE', $slug.'%')
											->lists($this->slug_field, $this->id_field);
        return $list;
    }

    public function sluggify($source) {
        // except vietnamese tone marks
        $source  = $this->exceptVnmToneMarks($source);
        // convert into slug
        $slug = $this->generateSlug($source);
        // unique
        $slug = $this->makeSlugUnique($slug);

        return $slug;
    }

}