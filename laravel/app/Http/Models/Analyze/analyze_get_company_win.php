<?php namespace App\Http\Models\Analyze;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class analyze_get_company_win extends Model {

	/**
	 * Таблица БД, используемая моделью.
	 *
	 * @var string
	 */
	protected $table = 'analyze_get_company_win';

	public static $fields = array();

	/**
	 * Атрибуты, исключенные из JSON-представления модели.
	 *
	 * @var array
	 */
	protected $hidden = array();

	/**
	 *  Установка охранных свойств модели
	 *
	 * @var array
	 */
	public $guarded = array('id');

	/** Отключение автоматических полей времени
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Указание доступных к массовому заполнению атрибутов
	 * @var array
	 */
	protected $fillable = array();

}
