<?php

/**
 * Description of order
 *
 * @author smpl
 */
class Gorder
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
     * Поле для групп
     * @var string
     */
    private $order_group_field;
    /**
     * ID группы с которой работаем
     * @var int
     */
    private $group_id;
    /**
     * Минимаьное значние с которого начинается сортировка
     * @var int
     */
    private $min_order = 1;
    /**
     * Максимально внесённый параметр сортировки
     * @var int
     */
    private $max_order;

    /**
     * Создаёт класс для управления сортировкой
     * @param string $t_name Имя таблицы в которой надо делать сортировку
     * @param string $order_group_field Имя главного поля
     * @param string $order_field Имя поля в котором находится сортировка
     * @return order
     */
    public function __construct($t_name, $order_group_field, $order_field = "order")
    {
        $this->t_name = $t_name;
        $this->order_field = $order_field;
        $this->order_group_field = $order_group_field;
    }

    /**
     * Исправляет сортировку колонок
     */
    public function fix_ordering()
    {
        $order_field = $this->order_field;
        $items = DB::select('id', $order_field)
            ->order_by($order_field)
            ->from($this->t_name)
            ->where(
                $this->order_group_field,
                is_null($this->group_id) ? 'IS' : '=',
                is_null($this->group_id) ? null : $this->group_id
            )
            ->execute();

        if ($items->count() == 0) {
            return true;
        }

        $arr_items = [];
        foreach ($items as $item) {
            array_push(
                $arr_items,
                [
                    'id' => $item['id'],
                    $order_field => $item[$order_field]
                ]
            );
        }

        for ($i = 0; $i < count($arr_items); $i++) {
            // Если первый элемент меньше единицы, исправим это. Сортировка должна начинаться с единицы.
            if ($i == 0 && $arr_items[$i][$order_field] == 0) {
                for ($i1 = 0; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] + 1;
                }
            }

            // Если первый элемент больше единицы, исправим это. Сортировка должна начинаться с единицы.
            if ($i == 0 && $arr_items[$i][$order_field] > 1) {
                $smesh = $arr_items[$i][$order_field] - 1;

                for ($i1 = 0; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] - $smesh;
                }
            }

            // Сравним этот объект со следующим, если он больше чем на 1, то сместим их вниз
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_field] > ($arr_items[$i][$order_field] + 1)) {
                $smesh = $arr_items[$i + 1][$order_field] - ($arr_items[$i][$order_field] + 1);

                for ($i1 = $i + 1; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] - $smesh;
                }
            }

            // Сравним этот объект со следующим, если они равны, то увеличим на 1 все следующие объекты
            if (isset($arr_items[$i + 1]) && $arr_items[$i + 1][$order_field] == $arr_items[$i][$order_field]) {
                for ($i1 = $i + 1; $i1 < count($arr_items); $i1++) {
                    $arr_items[$i1][$order_field] = $arr_items[$i1][$order_field] + 1;
                }
            }
        }

        // После того, как сформировали "исправленный" массив, закомитим все эти изменения в БД
        foreach ($arr_items as $item) {
            DB::update($this->t_name)
                ->set([$order_field => $item[$order_field]])
                ->where('id', '=', $item['id'])
                ->execute();
        }
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
        $column_order_val = $this->get_order($id);
        $prev = $this->get_prev($column_order_val);
        if (!is_null($prev) && $prev->count() > 0) {
            DB::update($this->t_name)->set([$this->order_field => DB::expr("`$this->order_field` + 1")])->where(
                "id",
                "=",
                $prev->get("id")
            )->execute();
            DB::update($this->t_name)->set([$this->order_field => DB::expr("`$this->order_field` - 1")])->where(
                "id",
                "=",
                $id
            )->execute();
            return true;
        } else {
            return false;
        }
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
        $order = $this->get_order($id);
        $next = $this->get_next($order);
        if (!is_null($next) && $next->count() > 0) {
            DB::update($this->t_name)->set(array($this->order_field => DB::expr("`$this->order_field` - 1")))->where(
                "id",
                "=",
                $next->get("id")
            )->execute();
            DB::update($this->t_name)->set(array($this->order_field => DB::expr("`$this->order_field` + 1")))->where(
                "id",
                "=",
                $id
            )->execute();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Сдвигает записи вниз, начиная с указанной записи
     * @param int $order Значения ордера
     * @return bool
     */
    public function update_after_del($order)
    {
        DB::update($this->t_name)
            ->set(["order" => DB::expr("`{$this->order_field}`-1")])
            ->where($this->order_field, ">", $order)
            ->where(
                $this->order_group_field,
                is_null($this->group_id) ? 'IS' : '=',
                is_null($this->group_id) ? null : $this->group_id
            )
            ->execute();
    }

    /**
     * Установить № группы, для которой будет производиться сортировка
     * @param int $group_id № группы
     * @param int $min_order Цифра, с которой начинается сортировка. По-умолчанию - 1.
     */
    public function set_group($group_id)
    {
        if (empty($group_id)) {
            $group_id = null;
        }

        $this->group_id = $group_id;

        $rez = DB::select([DB::expr("MAX(`{$this->order_field}`)"), "max_order"])
            ->from($this->t_name)
            ->where(
                $this->order_group_field,
                is_null($this->group_id) ? 'IS' : '=',
                is_null($this->group_id) ? null : $this->group_id
            )
            ->execute();

        if (is_null($rez->get("max_order"))) {
            $this->max_order = 0;
        } else {
            $this->max_order = $rez->get("max_order");
        }
    }

    /**
     * Возвращает значение поля сортировки для данной записи
     * @param type $id
     * @return mixed
     * int - Значение поля сортировки в случае успеха
     * NULL - в случае неудачи
     */
    private function get_order($id)
    {
        return DB::select()
            ->from($this->t_name)
            ->where("id", "=", $id)
            ->execute()
            ->get($this->order_field);
    }

    /**
     * Получает следущей записи
     * @param int $order позиция записи
     * @return Database_Result
     * NULL в случае неудачи или отсутия следущего значения
     */
    private function get_next($order)
    {
        if ($order < $this->max_order) {
            $rez = DB::select()
                ->from($this->t_name)
                ->where($this->order_field, "=", $order + 1)
                ->where(
                    $this->order_group_field,
                    is_null($this->group_id) ? 'IS' : '=',
                    is_null($this->group_id) ? null : $this->group_id
                )
                ->execute();
            return $rez;
        } else {
            return null;
        }
    }

    /**
     * Получает предыдущую запись
     * @param int $order позиция записи
     * @return Database_Result
     * NULL в случае неудачи или отсутвия преведущего значния
     */
    private function get_prev($order)
    {
        if ($order > $this->min_order) {
            $rez = DB::select()
                ->from($this->t_name)
                ->where($this->order_field, "=", $order - 1)
                ->where(
                    $this->order_group_field,
                    is_null($this->group_id) ? 'IS' : '=',
                    is_null($this->group_id) ? null : $this->group_id
                )
                ->execute();
            return $rez;
        } else {
            return null;
        }
    }
}

