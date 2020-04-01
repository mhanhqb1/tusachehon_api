<?php

use Fuel\Core\DB;

/**
 * Any query in Model Version
 *
 * @package Model
 * @created 2017-10-29
 * @version 1.0
 * @author AnhMH
 */
class Model_Product extends Model_Abstract {
    
    /** @var array $_properties field of table */
    protected static $_properties = array(
        'id',
        'name',
        'url',
        'price',
        'discount_price',
        'image',
        'thumb_image',
        'cate_id',
        'description',
        'detail',
        'seo_title',
        'seo_description',
        'seo_keyword',
        'seo_image',
        'created',
        'updated',
        'disable',
        'is_hot',
        'image2',
        'image3',
        'image4'
    );

    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array(
            'events'          => array('before_insert'),
            'mysql_timestamp' => false,
        ),
        'Orm\Observer_UpdatedAt' => array(
            'events'          => array('before_update'),
            'mysql_timestamp' => false,
        ),
    );

    /** @var array $_table_name name of table */
    protected static $_table_name = 'products';

    /**
     * Add update info
     *
     * @author AnhMH
     * @param array $param Input data
     * @return int|bool User ID or false if error
     */
    public static function add_update($param)
    {
        // Init
        $self = array();
        $isNew = false;
        $time = time();
        
        // Check if exist User
        if (!empty($param['id'])) {
            $self = self::find($param['id']);
            if (empty($self)) {
                self::errorNotExist('product_id');
                return false;
            }
        } else {
            $self = new self;
            $isNew = true;
        }
        
        // Upload image
        if (!empty($_FILES)) {
            $uploadResult = \Lib\Util::uploadImage(); 
            if ($uploadResult['status'] != 200) {
                self::setError($uploadResult['error']);
                return false;
            }
            $param['image'] = !empty($uploadResult['body']['image']) ? $uploadResult['body']['image'] : '';
            $param['image2'] = !empty($uploadResult['body']['image2']) ? $uploadResult['body']['image2'] : '';
            $param['image3'] = !empty($uploadResult['body']['image3']) ? $uploadResult['body']['image3'] : '';
            $param['image4'] = !empty($uploadResult['body']['image4']) ? $uploadResult['body']['image4'] : '';
        }
        
        // Set data
        if (!empty($param['name'])) {
            $self->set('name', $param['name']);
            $self->set('url', \Lib\Str::convertURL($param['name']));
        }
        if (!empty($param['price'])) {
            $self->set('price', $param['price']);
        }
        if (!empty($param['thumb_image'])) {
            $self->set('thumb_image', $param['thumb_image']);
        }
        if (!empty($param['cate_id'])) {
            $self->set('cate_id', $param['cate_id']);
        }
        if (!empty($param['description'])) {
            $self->set('description', $param['description']);
        }
        if (!empty($param['detail'])) {
            $self->set('detail', $param['detail']);
        }
        if (!empty($param['image'])) {
            $self->set('image', $param['image']);
        }
        if (!empty($param['image2'])) {
            $self->set('image2', $param['image2']);
        }
        if (!empty($param['image3'])) {
            $self->set('image3', $param['image3']);
        }
        if (!empty($param['image4'])) {
            $self->set('image4', $param['image4']);
        }
        if (!empty($param['seo_keyword'])) {
            $self->set('seo_keyword', $param['seo_keyword']);
        }
        if (!empty($param['seo_description'])) {
            $self->set('seo_description', $param['seo_description']);
        }
        if (isset($param['discount_price'])) {
            $self->set('discount_price', $param['discount_price']);
        }
        if (isset($param['is_hot'])) {
            $self->set('is_hot', $param['is_hot']);
        }
        $self->set('updated', $time);
        if ($isNew) {
            $self->set('created', $time);
        }
        
        // Save data
        if ($self->save()) {
            if (empty($self->id)) {
                $self->id = self::cached_object($self)->_original['id'];
            }
            return $self->id;
        }
        
        return false;
    }
    
    /**
     * Get list
     *
     * @author AnhMH
     * @param array $param Input data
     * @return array|bool
     */
    public static function get_list($param)
    {
        // Init
        $adminId = !empty($param['admin_id']) ? $param['admin_id'] : '';
        
        // Query
        $query = DB::select(
                self::$_table_name.'.*',
                array('cates.name', 'cate_name')
            )
            ->from(self::$_table_name)
            ->join('cates', 'left')
            ->on('cates.id', '=', self::$_table_name.'.cate_id')
        ;
                        
        // Filter
        if (!empty($param['name'])) {
            $query->where(self::$_table_name.'.name', 'LIKE', "%{$param['name']}%");
        }
        if (!empty($param['cate_id'])) {
            if (!is_array($param['cate_id'])) {
                $param['cate_id'] = explode(',', $param['cate_id']);
            }
            $query->where(self::$_table_name.'.cate_id', 'IN', $param['cate_id']);
        }
        if (!empty($param['is_discount'])) {
            $query->where(self::$_table_name.'.discount_price', '>', 0);
        }
        
        if (isset($param['disable']) && $param['disable'] != '') {
            $disable = !empty($param['disable']) ? 1 : 0;
            $query->where(self::$_table_name.'.disable', $disable);
        }
        
        // Pagination
        if (!empty($param['page']) && $param['limit']) {
            $offset = ($param['page'] - 1) * $param['limit'];
            $query->limit($param['limit'])->offset($offset);
        }
        
        // Sort
        if (!empty($param['sort'])) {
            if (!self::checkSort($param['sort'])) {
                self::errorParamInvalid('sort');
                return false;
            }

            $sortExplode = explode('-', $param['sort']);
            if ($sortExplode[0] == 'created') {
                $sortExplode[0] = self::$_table_name . '.created';
            }
            $query->order_by($sortExplode[0], $sortExplode[1]);
        } else {
            $query->order_by(self::$_table_name . '.created', 'DESC');
        }
        
        // Get data
        $data = $query->execute()->as_array();
        $total = !empty($data) ? DB::count_last_query(self::$slave_db) : 0;
        
        $discountProducts = array();
        if (!empty($param['get_discount_products'])) {
            $discountProducts = self::get_all(array(
                'sort' => 'discount_price-desc',
                'page' => 1,
                'limit' => 6,
                'is_discount' => 1
            ));
        }
        $newProducts = array();
        if (!empty($param['get_new_products'])) {
            $newProducts = self::get_all(array(
                'sort' => 'id-desc',
                'page' => 1,
                'limit' => 6
            ));
        }
        
        return array(
            'total' => $total,
            'data' => $data,
            'discount_products' => $discountProducts,
            'new_products' => $newProducts
        );
    }
    
    /**
     * Get detail
     *
     * @author AnhMH
     * @param array $param Input data
     * @return array|bool
     */
    public static function get_detail($param)
    {
        $id = !empty($param['id']) ? $param['id'] : '';
        $url = !empty($param['url']) ? $param['url'] : '';
        
        $query = DB::select(
                self::$_table_name.'.*',
                array('cates.name', 'cate_name'),
                array('cates.url', 'cate_url')
            )
            ->from(self::$_table_name)
            ->join('cates', 'LEFT')
            ->on('cates.id', '=', self::$_table_name.'.cate_id')
            ->where(self::$_table_name.'.disable', 0)
        ;
        if (!empty($url)) {
            $query->where(self::$_table_name.'.url', $url);
        } else {
            $query->where(self::$_table_name.'.id', $id);
        }
        $data = $query->execute()->offsetGet(0);
        if (empty($data)) {
            self::errorNotExist('product_id');
            return false;
        }
        if (!empty($param['get_new_products'])) {
            $data['new_products'] = self::get_all(array(
                'sort' => 'created-desc',
                'page' => 1,
                'limit' => 6
            ));
        }
        if (!empty($param['get_discount_products'])) {
            $data['discount_products'] = self::get_all(array(
                'sort' => 'discount_price-desc',
                'page' => 1,
                'limit' => 6,
                'is_discount' => 1
            ));
        }
        if (!empty($param['get_related_products'])) {
            $data['related_products'] = self::get_all(array(
                'sort' => 'id-desc',
                'page' => 1,
                'limit' => 12
            ));
        }
        
        return $data;
    }
    
    /**
     * Enable/Disable
     *
     * @author AnhMH
     * @param array $param Input data
     * @return int|bool User ID or false if error
     */
    public static function disable($param)
    {
        $ids = !empty($param['id']) ? $param['id'] : '';
        $disable = !empty($param['disable']) ? $param['disable'] : 0;
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        foreach ($ids as $id) {
            $self = self::del(array('id' => $id));
        }
        return true;
    }
    
    /**
     * Delete
     *
     * @author AnhMH
     * @param array $param Input data
     * @return Int|bool
     */
    public static function del($param)
    {
        $delete = self::deleteRow(self::$_table_name, array(
            'id' => $param['id']
        ));
        if ($delete) {
            return $param['id'];
        } else {
            return 0;
        }
    }
    
    /**
     * Get all
     *
     * @author AnhMH
     * @param array $param Input data
     * @return array|bool
     */
    public static function get_all($param)
    {
        // Init
        $adminId = !empty($param['admin_id']) ? $param['admin_id'] : '';
        
        if (!empty($param['product_url'])) {
            $cate = Model_Cate::find('first', array(
                'where' => array(
                    'url' => $param['cate_url']
                )
            ));
            if (!empty($cate['id'])) {
                $param['cate_id'] = $cate['id'];
            }
        }
        
        // Query
        $query = DB::select(
                self::$_table_name.'.*',
                array('cates.name', 'cate_name'),
                array('cates.url', 'cate_url')
            )
            ->from(self::$_table_name)
            ->join('cates', 'LEFT')
            ->on('cates.id', '=', self::$_table_name.'.cate_id')
            ->where(self::$_table_name.'.disable', 0)
        ;
                        
        // Filter
        if (!empty($param['name'])) {
            $query->where(self::$_table_name.'.name', 'LIKE', "%{$param['name']}%");
        }
        if (!empty($param['cate_id'])) {
            if (!is_array($param['cate_id'])) {
                $param['cate_id'] = explode(',', $param['cate_id']);
            }
            $query->where(self::$_table_name.'.cate_id', 'IN', $param['cate_id']);
        }
        if (isset($param['is_hot']) && $param['is_hot'] != '') {
            $query->where(self::$_table_name.'.is_hot', $param['is_hot']);
        }
        if (isset($param['is_discount']) && $param['is_discount'] != '') {
            $query->where(self::$_table_name.'.discount_price', '>', 0);
        }
        if (isset($param['is_home_slide']) && $param['is_home_slide'] != '') {
            $query->where(self::$_table_name.'.is_home_slide', $param['is_home_slide']);
        }
        if (isset($param['type']) && $param['type'] != '') {
            $query->where(self::$_table_name.'.type', $param['type']);
        }
        
        // Pagination
        if (!empty($param['page']) && $param['limit']) {
            $offset = ($param['page'] - 1) * $param['limit'];
            $query->limit($param['limit'])->offset($offset);
        }
        
        // Sort
        if (!empty($param['sort'])) {
            if (!self::checkSort($param['sort'])) {
                self::errorParamInvalid('sort');
                return false;
            }

            $sortExplode = explode('-', $param['sort']);
            if ($sortExplode[0] == 'created') {
                $sortExplode[0] = self::$_table_name . '.created';
            }
            $query->order_by($sortExplode[0], $sortExplode[1]);
        } else {
            $query->order_by(self::$_table_name . '.created', 'DESC');
        }
        
        // Get data
        $data = $query->execute()->as_array();
        
        return $data;
    }
}
