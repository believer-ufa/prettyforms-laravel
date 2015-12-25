<?php

namespace PrettyFormsLaravel;

use DB;

class GroupOrder
{
    /**
     * Таблица в которой надо сделать перестановку.
     *
     * @var string
     */
    private $table_name;

    /**
     * Имя поля по которому проводится сортировка
     *
     * @var string
     */
    private $order_field_name;

    /**
     * Поле для групп
     *
     * @var string
     */
    private $order_group_field;

    /**
     * ID группы с которой работаем
     *
     * @var int
     */
    private $group_id;

    /**
     * Минимаьное значние с которого начинается сортировка
     *
     * @var int
     */
    private $min_order = 1;

    /**
     * Максимально внесённый параметр сортировки
     *
     * @var int
     */
    private $max_order;

    /**
     * Создаёт класс для управления сортировкой
     *
     * @param string $t_name Имя таблицы в которой надо делать сортировку
     * @param string $order_group_field Имя главного поля
     * @param string $order_field Имя поля в котором находится сортировка
     *
     * @return order
     */
    public function __construct($t_name, $order_group_field, $order_field = 'order')
    {
        $this->table_name        = $t_name;
        $this->order_field_name  = $order_field;
        $this->order_group_field = $order_group_field;
    }

    /**
     * Исправляет сортировку колонок
     */
    public function fix_ordering()
    {
        $order_field = $this->order_field_name;

        $items = DB::table($this->table_name)
            ->select(['id', $order_field])
            ->orderBy($order_field)
            ->where($this->order_group_field, $this->group_id)
            ->get();

        if ($items->count() == 0) {
            return true;
        }

        $arr_items = [];
        foreach ($items as $arr_item) {
            $arr_items[] = [
                'id'         => $arr_item->id,
                $order_field => $arr_item->$order_field,
            ];
        }

        for ($i = 0; $i < count($arr_items); ++$i) {
            // Если первый элемент меньше единицы, исправим это. Сортировка должна начинаться с единицы.
            if ($i == 0 && $arr_items[$i][$order_field] == 0) {
                for ($i1 = 0; $i1 < count($arr_items); ++$i1) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] + 1;
                }
            }

            // Если первый элемент больше единицы, исправим это. Сортировка должна начинаться с единицы.
            if ($i == 0 && $arr_items[$i][$order_field] > 1) {
                $smesh = $arr_items[$i][$order_field] - 1;

                for ($i1 = 0; $i1 < count($arr_items); ++$i1) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] - $smesh;
                }
            }

            // Сравним этот объект со следующим, если он больше чем на 1, то сместим их вниз
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_field] > ($arr_items[$i][$order_field] + 1)) {
                $smesh = $arr_items[$i + 1][$order_field] - ($arr_items[$i][$order_field] + 1);

                for ($i1 = $i + 1; $i1 < count($arr_items); ++$i1) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] - $smesh;
                }
            }

            // Сравним этот объект со следующим, если они равны, то увеличим на 1 все следующие объекты
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_field] == $arr_items[$i][$order_field]) {
                for ($i1 = $i + 1; $i1 < count($arr_items); ++$i1) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] + 1;
                }
            }
        }

        // После того, как сформировали "исправленный" массив, закомитим все эти изменения в БД
        foreach ($arr_items as $arr_item) {
            DB::table($this->table_name)
                ->where('id', $arr_item['id'])
                ->update([$order_field => $arr_item[$order_field]]);
        }
    }

    /**
     * Возвращает значение ордера для новой записи
     *
     * @return int
     */
    public function get_insert_order()
    {
        ++$this->max_order;

        return $this->max_order;
    }

    /**
     * Поднять запись вверх(переставить местами с верхней)
     *
     * @param int $id Ид записи которую надо поднять
     *
     * @return bool
     * TRUE в случае успеха
     * FALSE в случае неудачи
     */
    public function up($id)
    {
        $column_order_val = $this->get_order($id);
        $prev             = $this->get_prev($column_order_val);
        if ( ! is_null($prev)) {
            DB::table($this->table_name)
                ->where('id', $prev->id)
                ->increment($this->order_field_name);

            DB::table($this->table_name)
                ->where('id', $id)
                ->decrement($this->order_field_name);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Переместить запись вниз(переставить местами с нижней)
     *
     * @param int $id ИД записи которую надо опустить
     *
     * @return bool
     * TRUE в случае успеха
     * FALSE в случае неудачи
     */
    public function down($id)
    {
        $current_order_value = $this->get_order($id);
        $next_record         = $this->get_next($current_order_value);
        if ( ! is_null($next_record)) {
            DB::table($this->table_name)
                ->where('id', '=', $next_record->id)
                ->decrement($this->order_field_name);

            DB::table($this->table_name)
                ->where('id', $id)
                ->increment($this->order_field_name);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Сдвигает записи вниз, начиная с указанной записи
     *
     * @param int $order Значения ордера
     *
     * @return bool
     */
    public function update_after_del($order)
    {
        DB::table($this->table_name)
            ->where($this->order_field_name, '>', $order)
            ->where($this->order_group_field, $this->group_id)
            ->decrement($this->order_field_name);
    }

    /**
     * Установить № группы, для которой будет производиться сортировка
     *
     * @param int $group_id № группы
     * @param int $min_order Цифра, с которой начинается сортировка. По-умолчанию - 1.
     */
    public function set_group($group_id)
    {
        if (empty($group_id)) {
            $group_id = null;
        }

        $this->group_id = $group_id;

        $max_order_count = DB::table($this->table_name)
            ->where($this->order_group_field, $this->group_id)
            ->max($this->order_field_name);

        $this->max_order = empty($max_order_count)
            ? 0
            : $max_order_count;
    }

    /**
     * Возвращает значение поля сортировки для данной записи
     *
     * @param type $record_id
     *
     * @return mixed
     * int - Значение поля сортировки в случае успеха
     * NULL - в случае неудачи
     */
    private function get_order($record_id)
    {
        return DB::table($this->table_name)
            ->where('id', $record_id)
            ->value($this->order_field_name);
    }

    /**
     * Возвращает следующую запись
     *
     * @param int $next_order_value позиция записи
     *
     * @return Database_Result
     * NULL в случае неудачи или отсутия следущего значения
     */
    private function get_next($next_order_value)
    {
        if ($next_order_value < $this->max_order) {
            return DB::table($this->table_name)
                ->where($this->order_field_name, $next_order_value + 1)
                ->where($this->order_group_field, $this->group_id)
                ->first();
        } else {
            return null;
        }
    }

    /**
     * Получает предыдущую запись
     *
     * @param int $order позиция записи
     *
     * @return Database_Result
     * NULL в случае неудачи или отсутвия преведущего значния
     */
    private function get_prev($order)
    {
        if ($order > $this->min_order) {
            return DB::table($this->table_name)
                ->where($this->order_field_name, '=', $order - 1)
                ->where($this->order_group_field, $this->group_id)
                ->first();
        } else {
            return null;
        }
    }
}
