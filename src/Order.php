<?

namespace PrettyFormsLaravel;

use DB;

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

    public function __construct($t_name, $order_field = 'order')
    {
        $this->t_name      = $t_name;
        $this->order_field = $order_field;
        $this->min_order   = 1;

        $rez = DB::table($this->t_name)->max($this->order_field);

        if (empty($rez)) {
            $this->max_order = 0;
        } else {
            $this->max_order = $rez;
        }
    }

    /**
     * Исправляет сортировку колонок
     */
    public function fix_ordering()
    {
        $order_f = $this->order_field;

        $items = DB::table($this->t_name)->select(['id', $order_f])->order_by($order_f)->get();
        if ($items->count() == 0) {
            return true;
        }

        $arr_items = [];
        foreach ($items as $item)
        {
            $arr_items[] = [
                'id'     => $item->id,
                $order_f => $item->$order_f
            ];
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
        foreach ($arr_items as $item)
        {
            DB::table($this->t_name)
                ->where('id', '=', $item['id'])
                ->update([$order_f => $item[$order_f]]);
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
                DB::table($this->t_name)
                    ->where("id", $prev->id)
                    ->increment($this->order_field);

                DB::table($this->t_name)
                    ->where("id", $id)
                    ->decrement($this->order_field);
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
        if (is_numeric($id))
        {
            $order = $this->get_order($id);
            $next = $this->get_next($order);
            if (!is_null($next) && $next->count() > 0)
            {
                DB::table($this->t_name)
                    ->where("id", "=", $next->get("id"))
                    ->decrement($this->order_field);

                DB::table($this->t_name)
                    ->where("id",$id)
                    ->increment($this->order_field);
                return true;
            }
        }
        return false;
    }

    /**
     * Сдвигает записи вниз, начиная с указанной записи
     * @param int $order Начиная с этой записи остальные сдвинуть вниз
     */
    public function update_after_del($order)
    {
        if (is_numeric($order))
        {
            DB::table($this->t_name)
                ->where($this->order_field,">",$order)
                ->decrement($this->order_field);
            return true;
        }
        return false;
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
        if (is_numeric($id))
        {
            $rez = DB::table($this->t_name)->where("id", $id)->get();

            if (is_numeric($rez->{$this->order_field}))
            {
                return $rez->{$this->order_field};
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
        if (is_numeric($order) && $order < $this->max_order)
        {
            return DB::table($this->t_name)->where($this->order_field, $order + 1)->get();
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
        if (is_numeric($order) && $order > $this->min_order)
        {
            return DB::table($this->t_name)->where($this->order_field, $order - 1)->get();
        }
        return null;
    }
}

