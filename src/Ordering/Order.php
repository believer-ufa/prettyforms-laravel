<?php

// Класс сортировки данных в таблице
class Order
{
    /**
     * Таблица в которой надо сделать перестановку.
     * @var string
     */
    private $t_name;
    /**
     * Имя поля по которому проводится сортировка
     * @var string
     */
    private $order_field;
    /**
     * Минимальное значение с которого начинается сортировка
     * @var int
     */
    private $min_order;
    /**
     * Максимально внесённый параметр сортировки
     * @var int
     */
    private $max_order;

    public function __construct($t_name, $order_field)
    {
        $this->t_name = $t_name;
        $this->order_field = $order_field;
        $this->min_order = 1;
        $rez = DB::select(array(DB::expr("MAX(`order`)"), "max_order"))->from($this->t_name)->execute();
        if (is_null($rez->get("max_order"))) {
            $this->max_order = 0;
        } else {
            $this->max_order = $rez->get("max_order");
        }
    }

    /**
     * Исправляет сортировку колонок
     */
    public function fix_ordering()
    {
        $order_f = $this->order_field;
        $table = $this->t_name;

        $items = DB::select('id', $order_f)->order_by($order_f)->from($table)->execute();
        if ($items->count() == 0) {
            return true;
        }

        $arr_items = array();
        foreach ($items as $item) {
            array_push($arr_items, array('id' => $item['id'], $order_f => $item[$order_f]));
        }

        //echo Arr::ListArray(Arr::Make1Array($arr_items, 'order'),'пустой массив');

        for ($i = 0; $i < count($arr_items); $i++) {
            // если первый элемент меньше единицы, исправим это. Сортировка должна начинаться с единицы
            if ($i == 0 && $arr_items[$i][$order_f] == 0) {
                for ($i1 = 0; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_f] = $arr_items[$i1][$order_f] + 1;
                }
            }

            // если первый элемент больше единицы, исправим это. Сортировка должна начинаться с единицы
            if ($i == 0 && $arr_items[$i][$order_f] > 1) {
                $smesh = $arr_items[$i][$order_f] - 1;

                for ($i1 = 0; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_f] = $arr_items[$i1][$order_f] - $smesh;
                }
            }

            // сравним этот объект со следующим, если он больше чем на 1, то сместим их вниз
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_f] > ($arr_items[$i][$order_f] + 1)) {
                $smesh = $arr_items[$i + 1][$order_f] - ($arr_items[$i][$order_f] + 1);

                for ($i1 = $i + 1; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_f] = $arr_items[$i1][$order_f] - $smesh;
                }
            }

            // сравним этот объект со следующим, если они равны, то увеличим на 1 все следующие объекты
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_f] == $arr_items[$i][$order_f]) {
                for ($i1 = $i + 1; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_f] = $arr_items[$i1][$order_f] + 1;
                }
            }
        }

        // После того, как сформировали "исправленный" массив, закомитим все эти изменения в БД
        foreach ($arr_items as $item) {
            DB::update($table)
                ->set(array($order_f => $item[$order_f]))
                ->where('id', '=', $item['id'])
                ->execute();
        }

        //echo Arr::ListArray(Arr::Make1Array($arr_items, 'order'),'пустой массив');
    }

    /**
     * Возвращает значение ордера для новой записи
     * @return int
     */
    public function get_insert_order()
    {
        $this->max_order++;
        return $this->max_order;
    }


    /**
     * Поднять запись вверх(переставить местами с верхней)
     * @param int $id Ид записи которую надо поднять
     * @return bool
     * TRUE в случае успеха
     * FALSE в случае неудачи
     */
    public function up($id)
    {
        if (is_numeric($id)) {
            $order = $this->get_order($id);
            $prev = $this->get_prev($order);
            if (!is_null($prev) && $prev->count() > 0) {
                DB::update($this->t_name)->set(
                    array($this->order_field => DB::expr("`$this->order_field` + 1"))
                )->where("id", "=", $prev->get("id"))->execute();
                DB::update($this->t_name)->set(
                    array($this->order_field => DB::expr("`$this->order_field` - 1"))
                )->where("id", "=", $id)->execute();
                return true;
            }
        }
        return false;
    }

    /**
     * Поднять запись вниз(переставить местами с нижней)
     * @param int $id ИД записи которую надо опустить
     * @return bool
     * TRUE в случае успеха
     * FALSE в случае неудачи
     */
    public function down($id)
    {
        if (is_numeric($id)) {
            $order = $this->get_order($id);
            $next = $this->get_next($order);
            if (!is_null($next) && $next->count() > 0) {
                DB::update($this->t_name)->set(
                    array($this->order_field => DB::expr("`$this->order_field` - 1"))
                )->where("id", "=", $next->get("id"))->execute();
                DB::update($this->t_name)->set(
                    array($this->order_field => DB::expr("`$this->order_field` + 1"))
                )->where("id", "=", $id)->execute();
                return true;
            }
        }
        return false;
    }

    /**
     * Сдвигает записи вниз, начиная с указанной записи
     * @param int $from_id Начиная с этой записи остальные сдвинуть вниз
     */
    public function update_after_del($order)
    {
        if (is_numeric($order)) {
            DB::update($this->t_name)->set(array("order" => DB::expr("`order`-1")))->where(
                $this->order_field,
                ">",
                $order
            )->execute();
            return true;
        }
        return false;
    }

    /**
     * Создаёт класс для управления сортировкой
     * @param string $t_name Имя таблицы в которой надо делать сортировку
     * @param string $order_field Имя поля в котором находится сортировка
     * @return order
     */
    public static function get_instance($t_name, $order_field = "order")
    {
        return new order($t_name, $order_field);
    }

    /**
     *
     * @param type $id
     * @return mixed
     * int - Значение поля сортировки в случае успеха
     * NULL - в случае неудачи
     */
    private function get_order($id)
    {
        if (is_numeric($id)) {
            $rez = DB::select()->from($this->t_name)->where("id", "=", $id)->execute();
            $r = $rez->get($this->order_field);
            if (is_numeric($r)) {
                return $r;
            }
        }
        return null;
    }

    /**
     * Получает следущей записи
     * @param int $order позиция записи
     * @return Database_Result
     * NULL в случае неудачи или отсутия следущего значения
     */
    private function get_next($order)
    {
        if (is_numeric($order) && $order < $this->max_order) {
            $rez = DB::select()->from($this->t_name)->where($this->order_field, "=", $order + 1)->execute();
            return $rez;
        }
        return null;
    }

    /**
     * Получает преведущей записи
     * @param int $order позиция записи
     * @return Database_Result
     * NULL в случае неудачи или отсутвия преведущего значния
     */
    private function get_prev($order)
    {
        if (is_numeric($order) && $order > $this->min_order) {
            $rez = DB::select()->from($this->t_name)->where($this->order_field, "=", $order - 1)->execute();
            return $rez;
        }
        return null;
    }
}

