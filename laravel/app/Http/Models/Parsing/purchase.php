<?php namespace App\Http\Models\Parsing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class purchase extends Model {

	/**
	 * Таблица БД, используемая моделью.
	 *
	 * @var string
	 */
	protected $table = 'purchase';

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
	public $guarded = array('id', 'organizer_id', 'provider_id', 'original_id');

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

	/**
	 * Таблица documents
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function documents()
	{
		return $this->belongsTo('App\Http\Models\Parsing\documents', 'purchase_id');
	}

	/**
	 * Таблица lots
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function lots()
	{
		return $this->belongsTo('App\Http\Models\Parsing\lots', 'purchase_id');
	}

}
