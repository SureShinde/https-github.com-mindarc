<?php

    class Biztech_Auspost_Model_Carrier_Pack {

        private $boxes = null;

        private $packed_boxes = null;

        private $level = -1;

        private $container_dimensions = null;

        function __construct($boxes = null, $container = null)
        {		
            if(isset($boxes) && is_array($boxes)) {
                $this->boxes = $boxes;
                $this->packed_boxes = array();

                if(!is_array($container)) {
                    $this->container_dimensions = $this->_calc_container_dimensions();
                }
                else {
                    if(!is_array($container)) {
                        $this->container_dimensions = $this->_calc_container_dimensions();
                    }
                    else {
                        if(!array_key_exists('length', $container) ||
                            !array_key_exists('width', $container)) {
                            throw new InvalidArgumentException("Function _pack only accepts array (length, width, height) as argument for $container");
                        }

                        $this->container_dimensions['length'] = $container['length'];
                        $this->container_dimensions['width'] = $container['width'];
                    }
                }
            }
        }

        function pack($boxes = null, $container = null) {
            if(isset($boxes) && is_array($boxes)) {
                $this->boxes = $boxes;
                $this->packed_boxes = array();
                $this->level = -1;
                $this->container_dimensions = null;

                if(!is_array($container)) {
                    $this->container_dimensions = $this->_calc_container_dimensions();
                }
                else {
                    if(!array_key_exists('length', $container) ||
                        !array_key_exists('width', $container)) {
                        throw new InvalidArgumentException("Pack function only accepts array (length, width, height) as argument for \$container");
                    }

                    $this->container_dimensions['length'] = $container['length'];
                    $this->container_dimensions['width'] = $container['width'];
                }
            }

            if(!isset($this->boxes)) {
                throw new InvalidArgumentException("Pack function only accepts array (length, width, height) as argument for \$boxes or no boxes given!");
            }

            $this->pack_level();
        }

        function get_remaining_boxes() {
            return $this->boxes;
        }

        function get_packed_boxes() {	
            return $this->packed_boxes;
        }

        function get_container_dimensions() {
            return $this->container_dimensions;
        }

        function get_container_volume() {
            if(!isset($this->container_dimensions)) {
                return 0;
            }

            return $this->_get_volume($this->container_dimensions);
        }

        function get_levels() {
            return $this->level + 1;
        }

        function get_packed_volume() {
            if(!isset($this->packed_boxes)) {
                return 0;
            }

            $volume = 0;

            for($i = 0; $i < count(array_keys($this->packed_boxes)); $i++) {
                foreach($this->packed_boxes[$i] as $box) {
                    $volume += $this->_get_volume($box);
                }
            }

            return $volume;
        }

        function get_remaining_volume() {
            if(!isset($this->packed_boxes)) {
                return 0;
            }

            $volume = 0;

            foreach($this->boxes as $box) {
                $volume += $this->_get_volume($box);
            }

            return $volume;
        }

        function get_level_dimensions($level = 0) {
            if($level < 0 || $level > $this->level || !array_key_exists($level, $this->packed_boxes)) {
                throw new OutOfRangeException("Level {$level} not found!");
            }

            $boxes = $this->packed_boxes;
            $edges = array('length', 'width', 'height');

            $le = $this->_calc_longest_edge($boxes[$level], $edges);
            $edges = array_diff($edges, array($le['edge_name']));

            $sle = $this->_calc_longest_edge($boxes[$level], $edges);

            return array(
                'width' => $le['edge_size'],
                'length' => $sle['edge_size'],
                'height' => $boxes[$level][0]['height']
            );
        }

        function _calc_longest_edge($boxes, $edges = array('length', 'width', 'height')) {
            if(!isset($boxes) || !is_array($boxes)) {
                throw new InvalidArgumentException('_calc_longest_edge function requires an array of boxes, '.typeof($boxes).' given');
            }

            $le = null;		// Longest edge
            $lef = null;	// Edge field (length | width | height) that is longest

            foreach($boxes as $k => $box) {
                foreach($edges as $edge) {
                    if(array_key_exists($edge, $box) && $box[$edge] > $le) {
                        $le = $box[$edge];	
                        $lef = $edge;
                    }
                }
            }

            return array(
                'edge_size' => $le,
                'edge_name' => $lef
            );
        }

        function _calc_container_dimensions() {
            if(!isset($this->boxes)){
                return array(
                    'length' => 0,
                    'width' => 0,
                    'height' => 0
                );
            }

            $boxes = $this->boxes;

            $edges = array('length', 'width', 'height');

            $le = $this->_calc_longest_edge($boxes, $edges);
            $edges = array_diff($edges, array($le['edge_name']));

            $sle = $this->_calc_longest_edge($boxes, $edges);

            return array(
                'length' => $sle['edge_size'],
                'width' => $le['edge_size'],
                'height' => 0
            );
        }

        function _swap($array, $el1, $el2) {
            if(!array_key_exists($el1, $array) || !array_key_exists($el2, $array)) {
                throw new InvalidArgumentException("Both element to be swapped need to exist in the supplied array");
            }

            $tmp = $array[$el1];
            $array[$el1] = $array[$el2];
            $array[$el2] = $tmp;

            return $array;
        }

        function _get_volume($box)  {	
            if(!is_array($box) || count(array_keys($box)) < 3) {
                throw new InvalidArgumentException("_get_volume function only accepts arrays with 3 values (length, width, height)");
            }

            $box = array_values($box);

            return $box[0] * $box[1] * $box[2]; 
        }

        private function _try_fit_box($box, $space)  {
            if(count($box) < 3) {
                throw new InvalidArgumentException("_try_fit_box function parameter $box only accepts arrays with 3 values (length, width, height)");
            }

            if(count($space) < 3) {
                throw new InvalidArgumentException("_try_fit_box function parameter $space only accepts arrays with 3 values (length, width, height)");
            }

            for($i = 0; $i < count($box); $i++) {
                if(array_key_exists($i, $space)) {
                    if($box[$i] > $space[$i]) {
                        return false;
                    }
                }
            }

            return true;
        }

        function _box_fits($box, $space) {
            $box = array_values($box);
            $space = array_values($space);

            if($this->_try_fit_box($box, $space)) {
                return true;
            }

            for($i = 0; $i < count($box); $i++) {
                $t_box = $box;

                unset($t_box[$i]);
                $t_keys = array_keys($t_box);
                $s_box = $this->_swap($box, $t_keys[0], $t_keys[1]);

                if($this->_try_fit_box($s_box, $space))
                    return true;
            }

            return false;
        }

        private function pack_level() {
            $biggest_box_index = null;
            $biggest_surface = 0;

            $this->level++;

            foreach($this->boxes as $k => $box)
            {
                $surface = $box['length'] * $box['width'];

                if($surface > $biggest_surface) {
                    $biggest_surface = $surface;
                    $biggest_box_index = $k;
                }
                elseif($surface == $biggest_surface) {
                    if(!isset($biggest_box_index) || (isset($biggest_box_index) && $box['height'] < $this->boxes[$biggest_box_index]['height']))
                        $biggest_box_index = $k;
                }
            }

            $biggest_box = $this->boxes[$biggest_box_index];
            $this->packed_boxes[$this->level][] = $biggest_box;

            $this->container_dimensions['height'] += $biggest_box['height'];

            unset($this->boxes[$biggest_box_index]);

            if(count($this->boxes) == 0)
                return;

            $c_area = $this->container_dimensions['length'] * $this->container_dimensions['width'];
            $p_area = $biggest_box['length'] * $biggest_box['width'];

            if($c_area - $p_area <= 0) {
                $this->pack_level();
            }
            else { // Space left, check if a package fits in
                $spaces = array();

                if($this->container_dimensions['length'] - $biggest_box['length'] > 0) {
                    $spaces[] = array(
                        'length' => $this->container_dimensions['length'] - $biggest_box['length'],
                        'width' => $this->container_dimensions['width'],
                        'height' => $biggest_box['height']
                    );
                }

                if($this->container_dimensions['width'] - $biggest_box['width'] > 0) {
                    $spaces[] = array(
                        'length' => $biggest_box['length'],
                        'width' => $this->container_dimensions['width'] - $biggest_box['width'],
                        'height' => $biggest_box['height']
                    );
                }

                foreach($spaces as $space) {
                    $this->_fill_space($space);
                }

                if(count($this->boxes) > 0)
                    $this->pack_level();
            }
        }

        private function _fill_space($space) {	

            $s_volume = $this->_get_volume($space);

            $fitting_box_index = null;
            $fitting_box_volume = null;

            foreach($this->boxes as $k => $box)
            {
                if($this->_get_volume($box) > $s_volume) {
                    continue;
                }

                if($this->_box_fits($box, $space)) {
                    $b_volume = $this->_get_volume($box);

                    if(!isset($fitting_box_volume) || $b_volume > $fitting_box_volume) {
                        $fitting_box_index = $k;
                        $fitting_box_volume = $b_volume;
                    }
                }
            }

            if(isset($fitting_box_index))
            {
                $box = $this->boxes[$fitting_box_index];

                $this->packed_boxes[$this->level][] = $this->boxes[$fitting_box_index];
                unset($this->boxes[$fitting_box_index]);

                $new_spaces = array();

                if($space['length'] - $box['length'] > 0) {
                    $new_spaces[] = array(
                        'length' => $space['length'] - $box['length'],
                        'width' => $space['width'],
                        'height' => $box['height']
                    );					
                }

                if($space['width'] - $box['width'] > 0) {
                    $new_spaces[] = array(
                        'length' => $box['length'],
                        'width' => $space['width'] - $box['width'],
                        'height' => $box['height']
                    );
                }

                if(count($new_spaces) > 0) {
                    foreach($new_spaces as $new_space) {
                        $this->_fill_space($new_space);
                    }
                }
            }
        }
    }

?>