<?php

namespace alina\mvc\model;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Str;
use alina\utils\Sys;
use Exception;
use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \Illuminate\Database\Capsule\Manager as Dal;
use \alina\AppExceptionValidation;
use Illuminate\Database\Query\Builder as BuilderAlias;
use Illuminate\Support\Collection as CollectionAlias;

// Laravel initiation
Loader::init();

class _BaseAlinaModel
{
    #region Required
    public    $table;
    public    $alias  = '';
    public    $pkName = 'id';
    public    $id     = NULL;
    protected $opts;
    public    $dataArrayIdentity;
    #endregion Required
    ##################################################
    #region Request
    /** @var BuilderAlias $q */
    public $q;
    public $o_GET        = NULL;
    public $apiOperators = [
        'lt_' => '<',
        'gt_' => '>',
        'eq_' => '=',
        'lk_' => 'LIKE',
    ];
    #endregion Request
    ##################################################
    #region Response
    /**@var  \stdClass */
    public $attributes;
    /**@var  CollectionAlias */
    public $collection       = [];
    public $state_ROWS_TOTAL = -1;
    public $pagesTotal       = 0;
    #endregion Response
    ##################################################
    #region Flags, CHeck-Points
    public $mode                        = 'SELECT';// Could be 'SELECT', 'UPDATE', 'INSERT', 'DELETE'
    public $state_DATA_FILTERED         = FALSE;
    public $state_DATA_VALIDATED        = FALSE;
    public $state_AFFECTED_ROWS         = NULL;
    public $state_EXCLUDE_COUNT_REQUEST = FALSE;
    public $matchedUniqueFields         = [];
    public $matchedConditions           = [];
    public $addAuditInfo                = FALSE;
    public $state_APPLY_GET_PARAMS      = FALSE;
    #emdregion Flags, CHeck-Points
    ##################################################
    #region Search Parameters
    /**
     * @var $sortDefault array
     * [['field1', 'DESC'], ['field2', 'ASC']]
     */
    public $sortDefault       = [];
    public $sortName          = NULL;
    public $sortAsc           = 'ASC';
    public $pageCurrentNumber = 0;
    public $pageSize          = 15;
    #endregion Search Parameters
    ##################################################
    #region Constructor
    public function __construct($opts = NULL)
    {
        $this->attributes = (object)[];
        $this->setPkValue(NULL);
        if ($opts) {
            $opts       = Data::toObject($opts);
            $this->opts = $opts;
            if (isset($opts->table)) {
                $this->table = $opts->table;
            }
        }
        $this->alias = $this->table;
        $this->buildDefaultData();
    }
    #endregion Constructor
    ##################################################
    #region SELECT
    public function getById($id)
    {
        return $this->getOne([$this->pkName => $id]);
    }

    public function getOne($conditions = [])
    {
        $this->state_EXCLUDE_COUNT_REQUEST = TRUE;
        $data                              = $this->q()->where($conditions)->first();
        if (empty($data)) {
            $data = (object)[];
        }
        $this->attributes = Data::mergeObjects($this->attributes, $data);
        if ($this->attributes->{$this->pkName}) {
            $this->setPkValue($this->attributes->{$this->pkName});
        }
        $this->state_EXCLUDE_COUNT_REQUEST = FALSE;

        return $this->attributes;
    }

    public function getAll($conditions = [], $backendSortArray = NULL, $limit = NULL, $offset = NULL)
    {
        $this->collection =
            $this
                ->q()
                ->where($conditions)
                ->get();

        return $this->collection;
    }

    protected function getModelByUniqueKeys($data, $uniqueKeys = NULL)
    {
        $data = Data::toObject($data);
        if (empty($uniqueKeys)) {
            $uniqueKeys = $this->uniqueKeys();
        }
        foreach ($uniqueKeys as $uniqueFields) {
            $conditions = [];
            $uFields    = [];
            if (!is_array($uniqueFields)) {
                $uniqueFields = [$uniqueFields];
            }
            foreach ($uniqueFields as $uf) {
                if (property_exists($data, $uf)) {
                    $conditions[$uf] = $data->{$uf};
                    $uFields[]       = $uf;
                }
                // If $data doesn't contain one of Unique Keys,
                // simply skip this check entirely.
                else {
                    continue 2; // Skips this and previous "foreach".
                }
            }
            // Check if similar model exists.
            $m = new static(['table' => $this->table]);
            $q = $m->q();
            $q->where($conditions);
            /*
             * If $data already contains a field with Primary Key of a model,
             * we should exclude this model while the check.
             */
            if (property_exists($data, $this->pkName) && !empty($data->{$this->pkName})) {
                $q->where($this->pkName, '!=', $data->{$this->pkName});
            }
            // Check only NOT is_deleted
            if ($m->tableHasField('is_deleted')) {
                $q->where('is_deleted', '!=', 1);
            }
            $aRecord = $q->first();
            if (isset($aRecord) && !empty($aRecord)) {
                $this->matchedUniqueFields = $uFields;
                $this->matchedConditions   = $conditions;
                $this->attributes          = $aRecord;
                $this->setPkValue($aRecord->{$this->pkName});

                return $aRecord;
            }
        }

        return FALSE;
    }

    ###############
    #region Get With References
    public function getAllWithReferencesPart1($conditions = [], $alias = NULL)
    {
        $q = $this->q($alias);
        $q->select(["{$this->alias}.*"]);
        //API WHERE
        $q->where($conditions);
        if ($this->state_APPLY_GET_PARAMS) {
            //SEARCH from _GET
            $this->apiUnpackGetParams();
            $this->qApplyGetSearchParams();
        }
        //Has One JOINs.
        $this->qJoinHasOne();
        #####
        if (method_exists($this, 'hookGetWithReferences')) {
            $this->hookGetWithReferences($q);
        }

        #####
        return $q;
    }

    public function getAllWithReferencesPart2($backendSortArray = NULL, $pageSize = NULL, $pageCurrentNumber = NULL, $paginationVersa = FALSE)
    {
        $q = $this->q;
        //COUNT
        if ($this->state_EXCLUDE_COUNT_REQUEST) {
            $this->state_ROWS_TOTAL            = 1;
            $this->state_EXCLUDE_COUNT_REQUEST = FALSE;
        }
        else {
            $this->state_ROWS_TOTAL = $q->count();
        }
        //ORDER
        $this->qApiOrder($backendSortArray);
        //LIMIT / OFFSET
        $this->qApiLimitOffset($pageSize, $pageCurrentNumber, $paginationVersa);
        //Final query.
        // Sys::fDebug($q->toSql());
        $this->collection = $q->get();
        //Has Many JOINs.
        $this->joinHasMany();

        return $this->collection;
    }

    public function getAllWithReferences($conditions = [], $backendSortArray = NULL, $pageSize = NULL, $pgeCurrentNumber = NULL, $paginationVersa = FALSE)
    {
        /** @var $q BuilderAlias object */
        $q   = $this->getAllWithReferencesPart1($conditions);
        $res = $this->getAllWithReferencesPart2($backendSortArray, $pageSize, $pgeCurrentNumber, $paginationVersa);

        return $res;
    }

    //ToDo: see also $this->getOne VERY similar logic
    public function getOneWithReferences($conditions = [])
    {
        $this->state_EXCLUDE_COUNT_REQUEST = TRUE;
        $attributes                        = $this->getAllWithReferences($conditions, [], 1, 0)->first();
        if (empty($attributes)) {
            $attributes = (object)[];
        }
        if (isset($attributes->{$this->pkName})) {
            $this->setPkValue($attributes->{$this->pkName});
        }
        $this->attributes = Data::mergeObjects($this->attributes, $attributes);

        return $this->attributes;
    }
    #rendegion Get With References
    ###############
    #endregion SELECT
    ##################################################
    #region UPSERT
    public function upsert($data)
    {
        $data = Data::toObject($data);
        if (isset($data->{$this->pkName}) && !empty($data->{$this->pkName})) {
            $this->updateById($data);
        }
        else {
            $this->insert($data);
        }

        return $this;
    }

    public function upsertByUniqueFields($data, $uniqueKeys = NULL)
    {
        $aRecord = $this->getModelByUniqueKeys($data, $uniqueKeys);
        if ($aRecord) {
            $conditions = $this->matchedConditions;
            $this->update($data, $conditions);
        }
        else {
            $this->insert($data);
        }

        return $this;
    }
    #endregion UPSERT
    ##################################################
    #region INSERT
    public function insert($data)
    {
        $this->mode = 'INSERT';
        $pkName     = $this->pkName;
        $data       = Data::toObject($data);
        $data       = Data::mergeObjects($this->buildDefaultData(), $data);
        $dataArray  = $this->prepareDbData($data);
        #####
        if (method_exists($this, 'hookRightBeforeSave')) {
            $this->hookRightBeforeSave($dataArray);
        }
        #####
        $id               = $this->q()->insertGetId($dataArray, $pkName);
        $this->attributes = $data = Data::toObject($dataArray);
        $this->setPkValue($id, $data);
        #####
        GlobalRequestStorage::setPlus1('BaseModelQueries');
        #####
        if (method_exists($this, 'hookRightAfterSave')) {
            $this->hookRightAfterSave($data);
        }
        #####
        $this->resetFlags();

        return $this->attributes;
    }
    #endregion INSERT
    ##################################################
    #region UPDATE
    /**
     * Updates record by Primary Key.
     * PK could be passed either in $data object or as the second parameter separately.
     * @param $data array|\stdClass
     * @param null|mixed $id
     * @return \stdClass
     * @throws Exception
     * @throws AppExceptionValidation
     */
    public function updateById($data, $id = NULL)
    {
        $data   = Data::toObject($data);
        $pkName = $this->pkName;
        if (isset($id) && !empty($id)) {
            $pkValue = $id;
        }
        else if (isset($data->{$pkName}) && !empty($data->{$pkName})) {
            $pkValue = $data->{$pkName};
        }
        else if (isset($this->id) && !empty($this->id)) {
            $pkValue = $this->id;
        }
        if (!isset($pkValue) || empty($pkValue)) {
            $table   = $this->table;
            $message = "Cannot UPDATE row in table {$table}. Primary Key is not set.";
            Message::setDanger($message);
            throw new AppExceptionValidation($message);
        }
        $conditions = [$pkName => $pkValue];
        $this->update($data, $conditions);
        $this->attributes = Data::mergeObjects($this->attributes, $data);
        $this->setPkValue($pkValue, $data);

        return $this->attributes;
    }

    public function update($data, $conditions = [])
    {
        $this->mode = 'UPDATE';
        $pkName     = $this->pkName;
        $data       = Data::toObject($data);
        $dataArray  = $this->prepareDbData($data);
        ##################################################
        if (method_exists($this, 'hookRightBeforeSave')) {
            $this->hookRightBeforeSave($dataArray);
        }
        ##################################################
        $this->state_AFFECTED_ROWS =
            $this->q()
                ->where($conditions)
                ->update($dataArray);
        #####
        GlobalRequestStorage::setPlus1('BaseModelQueries');
        #####
        if ($this->state_AFFECTED_ROWS == 1) {
            $this->attributes = Data::mergeObjects($this->attributes, Data::toObject($dataArray));
            if (isset($this->attributes->{$this->pkName})) {
                $this->setPkValue($this->attributes->{$this->pkName});
            }
        }
        ##################################################
        if (method_exists($this, 'hookRightAfterSave')) {
            $this->hookRightAfterSave($data);
        }
        ##################################################
        $this->resetFlags();

        return $this;
    }
    #endregion UPDATE
    ##################################################
    #region DELETE
    public function delete(array $conditions)
    {
        $this->mode        = 'DELETE';
        $affectedRowsCount = $this->q()
            ->where($conditions)
            ->delete();
        #####
        GlobalRequestStorage::setPlus1('BaseModelQueries');
        #####
        $this->state_AFFECTED_ROWS = $affectedRowsCount;
        $this->resetFlags();

        return $this->state_AFFECTED_ROWS;
    }

    public function deleteById($id)
    {
        $pkName  = $this->pkName;
        $pkValue = $id;

        return $this->delete([$pkName => $pkValue]);
    }

    public function smartDeleteById($id, $additionalData = NULL)
    {
        if ($this->tableHasField('is_deleted') || (isset($additionalData) && !empty($additionalData))) {
            $pkName = $this->pkName;
            $data   = (isset($additionalData) && !empty($additionalData))
                ? Data::toObject($additionalData)
                : new \stdClass();
            // Even if there is no is_deleted in this->fields, it does not brings error
            // due to $this->bindModel functionality.
            $data->is_deleted = 1;
            $data->{$pkName}  = $id;
            // When $data contains Primary Key, there is ni necessity to set it as the second parameter.
            $this->updateById($data);
        }
        else {
            // If table does not participate in Audit process,
            // simply DELETE row from database.
            $this->deleteById($id);
        }
    }
    #endregion DELETE
    ##################################################
    #region API, LIMIT, ORDER
    /**
     * Prepare paginated API response.
     * @return array with the next structure
     * [
     *  'total' => int total amount of rows,
     *  'page' => int number of page,
     *  'models' => array of objects which represent database rows,
     * ]
     */
    public function qApiResponsePaginated()
    {
        /** @var $q BuilderAlias object */
        $q = $this->q;
        // COUNT
        $total = $q->count();
        // ORDER
        $this->qApiOrder();
        // LIMIT partial
        $this->qApiLimitOffset();
        // Result
        //fDebug($q->toSql());
        $this->collection = $q->get();
        $page             = $this->pageCurrentNumber;
        $output           = ["total" => $total, "page" => $page, "models" => $this->collection];

        //fDebug($output);
        return $output;
    }

    /**
     * Apply ORDER BY to query
     * @param array array $backendSortArray
     * @return BuilderAlias object
     */
    protected function qApiOrder($backendSortArray = [])
    {
        /** @var $q BuilderAlias object */
        $q = $this->q;
        #####
        if (isset($backendSortArray) && !empty($backendSortArray)) {
            $sortArray = $backendSortArray;
        } #####
        else {
            // User Defined Sort parameters.
            $sortArray = $this->calcSortNameSortAscData($this->sortName, $this->sortAsc);
            if (empty($sortArray)) {
                $sortArray = $this->sortDefault;
            }
        }
        $this->qOrderByArray($sortArray);

        return $q;
    }

    /**
     * Apply complex ORDER BY to query
     * @param $orderArray array
     * @return BuilderAlias object
     */
    protected function qOrderByArray($orderArray = [])
    {
        /** @var $q BuilderAlias object */
        $q = $this->q;
        if (empty($orderArray)) {
            return $q;
        }
        if (is_string($orderArray)) {
            $orderArray = [[$orderArray, 'ASC']];
        }
        foreach ($orderArray as $orderBy) {
            if (count($orderBy) !== 2) {
                //ToDo: Validate all necessary parameters.
                continue;
            }
            list($field, $direction) = $orderBy;
            $q->orderBy($field, $direction);
        }

        return $q;
    }

    /**
     * Apply LIMIT/OFFSET to a query
     * @param int|null $backendLimit
     * @param int|null $backendPageCurrentNumber
     * @param bool $backendVersa
     * @return BuilderAlias object
     */
    protected function qApiLimitOffset(int $backendLimit = NULL, int $backendPageCurrentNumber = NULL, bool $backendVersa = FALSE): BuilderAlias
    {
        #####
        if ($backendLimit !== NULL) {
            $this->pageSize = $backendLimit;
        }
        if ($backendPageCurrentNumber !== NULL) {
            $this->pageCurrentNumber = $backendPageCurrentNumber;
        }
        #####
        $q                       = $this->q;
        $PG                      = Data::paginator($this->state_ROWS_TOTAL, $this->pageCurrentNumber, $this->pageSize, $backendVersa);
        $this->pagesTotal        = $PG->pages;
        $this->pageCurrentNumber = $PG->page;
        $this->pageSize          = $PG->limit;
        $offset                  = $PG->offset;
        $q->skip($offset)->take($this->pageSize);
        #####
        GlobalRequestStorage::set("{$this->alias}/pageCurrentNumber", $this->pageCurrentNumber);
        GlobalRequestStorage::set("{$this->alias}/pageSize", $this->pageSize);
        GlobalRequestStorage::set("{$this->alias}/rowsTotal", $this->state_ROWS_TOTAL);
        GlobalRequestStorage::set("{$this->alias}/pagesTotal", $this->pagesTotal);
        #####
        #####
        return $q;
    }

    /**
     * Add Audit Info if possible.
     * @return BuilderAlias object
     */
    protected function qApiJoinAuditInfo()
    {
        /** @var $q BuilderAlias object */
        $q          = $this->q;
        $thisFields = $this->fields();
        $alias      = $this->alias;
        // Join Creator if possible
        if (array_key_exists('created_by', $thisFields)) {
            $q->addSelect([
                'pc.first_name as created_first_name',
                'pc.last_name as created_last_name',
            ]);
            $q->leftJoin('person as pc', 'pc.person_id', '=', "{$alias}.created_by");
        }
        if (array_key_exists('modified_by', $thisFields)) {
            $q->addSelect([
                'pm.first_name as modified_first_name',
                'pm.last_name as modified_last_name',
            ]);
            $q->leftJoin('person as pm', 'pm.person_id', '=', "{$alias}.modified_by");
        }

        return $q;
    }

    /**
     * Sets $this->o_GET
     */
    protected function apiUnpackGetParams()
    {
        $R            = Request::obj();
        $R_GET        = $R->GET;
        $this->o_GET  = new \stdClass();
        $vocGetSearch = $this->vocGetSearch();
        foreach ($vocGetSearch as $short => $full) {
            /*
             * NOTE:
             * It sets Property even if it is equal to empty string ''.
             * Check right in API callbacks, if we need to SELECT models, WHERE the Prop is ''.
             * Otherwise, Front-end is to be adjusted to avoid of sending GET params, when they are not necessary.
             */
            if (isset($R_GET->{$short})) {
                $this->o_GET->{$full} = $R_GET->{$short};
            }
        }
        // Additional Special Condition $this->sortName, $this->sortAsc
        if (isset($R_GET->sa)) {
            $this->sortAsc = $R_GET->sa;
        }
        if (isset($R_GET->sn)) {
            $this->sortName = $R_GET->sn;
        }
        // Additional Special Condition $this->pageSize, $this->page
        if (isset($R_GET->ps)) {
            $this->pageSize = $R_GET->ps;
        }
        //ToDo: Rename to pn.
        if (isset($R_GET->p)) {
            $this->pageCurrentNumber = $R_GET->p;
        }

        return $this->o_GET;
    }
    #endregion API, LIMIT, ORDER
    ##################################################
    #region FILTER, VALIDATE
    /**
     * Filter received $data according $this fields params.
     * @param \stdClass $data
     * @return $this
     */
    public function applyFilters(\stdClass $data)
    {
        if ($this->state_DATA_FILTERED) {
            return $this;
        }
        $filters = [];
        $fields  = $this->fields();
        #####
        foreach ($fields as $fieldNameCfg => $params) {
            if (property_exists($data, $fieldNameCfg)) {
                #####
                if ($this->mode === 'INSERT' && empty($data->{$fieldNameCfg}) && isset($params['default'])) {
                    $data->{$fieldNameCfg} = $params['default'];
                    continue;
                }
                #####
                ##################################################
                if (isset($params['filters']) && !empty($params['filters'])) {
                    $filters[$fieldNameCfg] = $params['filters'];
                }
            }
            else {
                if ($this->mode === 'INSERT' && isset($params['default'])) {
                    $data->{$fieldNameCfg} = $params['default'];
                }
            }
        }
        Data::filterObject($data, $filters);
        #####
        $this->state_DATA_FILTERED = TRUE;

        return $this;
    }

    public function validate(\stdClass $data)
    {
        if ($this->state_DATA_VALIDATED) {
            return $this;
        }
        $validators = [];
        $fields     = $this->fields();
        foreach ($fields as $fieldNameCfg => $params) {
            if (property_exists($data, $fieldNameCfg)) {
                if (isset($params['validators']) && !empty($params['validators'])) {
                    $validators[$fieldNameCfg] = $params['validators'];
                }
            }
        }
        Data::validateObject($data, $validators);
        $this->validateUniqueKeys($data);
        $this->state_DATA_VALIDATED = TRUE;

        return $this;
    }

    public function validateUniqueKeys($data)
    {
        if ($this->mode === 'UPDATE') {
            if (!property_exists($data, $this->pkName) || empty($data->{$this->pkName})) {
                return $this;
            }
        }
        if ($this->getModelByUniqueKeys($data)) {
            $fields  = strtoupper(implode(', ', $this->matchedUniqueFields));
            $table   = strtoupper($this->table);
            $message = "{$table} with such {$fields} already exists";
            Message::setDanger($message);
            throw new AppExceptionValidation($message);
        }

        return $this;
    }

    /**
     * Is used AFTER filtering and validation.
     * Responsible to create array of field=>value pairs,
     * AND allows only those field names, which are listed in $this->fields() array.
     * Minimizes conflicts.
     * It does NOT change input object.
     * @param \stdClass $data
     * @return array
     */
    protected function restrictIdentityAutoincrementReadOnlyFields($data)
    {
        $dataArray = [];
        $fields    = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                if ($this->isFieldIdentity($name)) {
                    $this->dataArrayIdentity[$name] = $data->{$name};
                }
                else {
                    $dataArray[$name] = $data->{$name};
                }
            }
        }

        return $dataArray;
    }

    #endregion FILTER, VALIDATE
    ##################################################
    #region Helpers
    protected function calcSortNameSortAscData($sortName, $sortAsc)
    {
        if (empty($sortName)) {
            return NULL;
        }
        // Sorting functionality
        // Pre-saving backward compatibility.
        $sn        = explode(',', $sortName);
        $sa        = explode(',', $sortAsc);
        $sortArray = [];
        foreach ($sn as $i => $n) {
            $asc         = isset($sa[$i]) ? Data::getSqlDirection($sa[$i]) : 'ASC';
            $sortArray[] = [$n, $asc];
        }

        return $sortArray;
    }

    protected function resetFlags()
    {
        $this->mode                 = 'SELECT';
        $this->state_DATA_FILTERED  = FALSE;
        $this->state_DATA_VALIDATED = FALSE;
        $this->matchedUniqueFields  = [];
        $this->matchedConditions    = [];
    }

    /**
     * Creates $defaultRawObj with default values for DB.
     * @return \stdClass object $defaultRawObj.
     */
    protected function buildDefaultData()
    {
        $fields        = $this->fields();
        $defaultRawObj = new \stdClass();
        foreach ($fields as $f => $props) {
            if (array_key_exists('default', $props)) {
                $defaultRawObj->{$f} = $props['default'];
            }
            else {
                $defaultRawObj->{$f} = NULL;
            }
        }
        $this->attributes = $defaultRawObj;

        return $this->attributes;
    }

    protected function prepareDbData($data)
    {
        $data = Data::toObject($data);
        $this->applyFilters($data);
        $this->validate($data);
        if ($this->addAuditInfo) {
            $this->addAuditInfo($data, $this->mode);
        }
        $dataArray = $this->restrictIdentityAutoincrementReadOnlyFields($data);

        return $dataArray;
    }

    public function tableHasField($fieldName)
    {
        return array_key_exists($fieldName, $this->fields());
    }

    /**
     * Add Audit Information to $data object.
     * @param \stdClass $data
     * @param string|null $saveMode
     * @return null
     * ToDo: Consider to delete.
     */
    protected function addAuditInfo(\stdClass $data, $saveMode = NULL)
    {
        if ($saveMode === NULL) {
            $saveMode = $this->mode;
        }
        $user_id             = AlinaCurrentUserId();
        $now                 = AlinaGetNowInDbFormat();
        $data->modified_by   = $user_id;
        $data->modified_date = $now;
        if ($saveMode === 'INSERT') {
            $data->created_by   = $user_id;
            $data->created_date = $now;
        }

        return NULL;
    }

    /**
     * Detects if a Field is AutoIncremental.
     * @param $fieldName string
     * @return bool
     */
    public function isFieldIdentity($fieldName)
    {
        $fieldsIdentity = $this->fieldsIdentity();

        return in_array($fieldName, $fieldsIdentity);
    }

    /**
     * Returns array of arrays.
     * Nested arrays contain list of field names, which are Unique Keys together.
     * Example:
     * [
     *  ['email'],
     *  ['row', 'column'],
     * ]
     */
    protected function uniqueKeys()
    {
        return [];
    }

    protected function fieldsIdentity()
    {
        return [
            $this->pkName,
        ];
    }

    /**
     * @param string $alias
     * @return BuilderAlias
     */
    public function q($alias = NULL)
    {
        /**
         * ATTENTION: Important security fix in order to avoid accident start of a query,
         * while a previous is in progress.
         */
        if (isset($this->q) && !empty($this->q)) {
            $this->q = NULL;
        }
        $this->alias = $alias ? $alias : $this->alias;
        if ($this->mode === 'INSERT' || $this->mode === 'DELETE' || $alias == -1) {
            $this->q = Dal::table("{$this->table}");
        }
        else {
            $this->q = Dal::table("{$this->table} AS {$this->alias}");
        }
        #####
        //ToDo: Make Conditional
        GlobalRequestStorage::setPlus1('BaseModelQueries');

        #####
        return $this->q;
    }

    /**
     * Initial list of fields. @see fieldStructureExample
     */
    public function fields()
    {
        $fields = [];
        $items  = [];
        $items  = Dal::table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('table_name', '=', $this->table)
            ->where('table_schema', '=', AlinaCfg('db/database'))
            ->pluck('COLUMN_NAME');
        foreach ($items as $v) {
            if (!empty($v)) {
                $fields[$v] = [];
            }
        }
        if ($this->table === 'tale') {
            GlobalRequestStorage::set('$items', $items);
        }

        return $fields;
        ##################################################
        // Previous approach
        // $table  = $this->table;
        // $m      = new static(['table' => $table]);
        // $q      = $m->q();
        // $item   = $q->first();
        // foreach ($item as $i => $v) {
        //     $fields[$i] = [];
        // }
        // return $fields;
        ##################################################
    }

    public function getFieldsMetaInfo()
    {
        $fields   = $this->fields();
        $pkName   = $this->pkName;
        $identity = $this->fieldsIdentity();
        $unique   = $this->uniqueKeys();

        return [
            'fields'   => $fields,
            'pkName'   => $pkName,
            'identity' => $identity,
            'unique'   => $unique,
        ];
    }

    public function raw($expression)
    {
        return Dal::raw($expression);
    }

    protected function vocGetSearch()
    {
        $vocSpecial = [];
        if (method_exists($this, 'vocGetSearchSpecial')) {
            $vocSpecial = $this->vocGetSearchSpecial();
        }
        $vocGetSearch = [
            // Pagination
            'ps' => 'pageSize',
            'p'  => 'page',
            // Sorting functionality
            'sa' => 'sortAsc',
            'sn' => 'sortName',
            // Search
            'q'  => 'search',
        ];

        return array_merge($vocSpecial, $vocGetSearch);
    }

    protected function vocGetSearchSpecial()
    {
        $res    = [];
        $R      = Request::obj();
        $R_GET  = $R->GET;
        $fields = $this->fields();
        $fNames = array_keys($fields);
        foreach ($fNames as $f) {
            foreach ($R_GET as $gF => $gV) {
                if (\alina\utils\Str::endsWith($gF, $f)) {
                    $res[$gF] = $gF;
                }
            }
        }

        return $res;
    }

    protected function qApplyGetSearchParams()
    {
        //ToDo: Check $q, $this->o_GET emptiness.
        $q = $this->q;
        foreach ($this->o_GET as $f => $v) {
            $t = $this->alias;
            //The simplest search case.
            if ($this->tableHasField($f)) {
                if (is_array($v)) {
                    $q->whereIn("{$t}.{$f}", $v);
                }
                else {
                    $q->where("{$t}.{$f}", 'LIKE', "%{$v}%");
                }
            }
            //API GET operators.
            $apiOperators = $this->apiOperators;
            foreach ($apiOperators as $o => $oV) {
                if (\alina\utils\Str::startsWith($f, $o)) {
                    $fName = implode('', explode($o, $f, 2));
                    if ($this->tableHasField($fName)) {
                        switch ($o) {
                            case ('lt_'):
                                $q->where("{$t}.{$fName}", '<', $v);
                                break;
                            case ('gt_'):
                                $q->where("{$t}.{$fName}", '>', $v);
                                break;
                            case ('eq_'):
                                $q->where("{$t}.{$fName}", '=', $v);
                                break;
                            case ('lk_'):
                                $q->where("{$t}.{$fName}", 'LIKE', "%{$v}%");
                                break;
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function g($f)
    {
        if (isset($this->attributes->{$f})) {
            return $this->attributes->{$f};
        }

        return FALSE;
    }

    protected function setPkValue($id, \stdClass $data = NULL)
    {
        $this->{$this->pkName}             = $id;
        $this->id                          = $id;
        $this->attributes->{$this->pkName} = $id;
        if ($data) {
            $data->{$this->pkName} = $id;
        }

        return $this;
    }

    #endregion Helpers
    ##################################################
    #region relations
    protected function qJoinHasOne()
    {
        (new referenceProcessor($this))->joinHasOne();
    }

    protected function joinHasMany()
    {
        $forIds        = $this->collection->pluck($this->pkName);
        $qHasManyArray = (new referenceProcessor($this))->joinHasMany([], $forIds);
        foreach ($qHasManyArray as $rName => $q) {
            //ToDO: Hardcoded id
            $qResult = $q->get();
            foreach ($this->collection as $thisModelAttributes) {
                $thisModelAttributes->{$rName} = [];
                foreach ($qResult as $row) {
                    if ($thisModelAttributes->{$this->pkName} === $row->main_id) {
                        $thisModelAttributes->{$rName}[] = $row;
                    }
                }
            }
        }
    }

    ##################################################
    protected function referencesSources()
    {
        return [];
    }

    public function getReferencesSources()
    {
        $sources = [];
        if (method_exists($this, 'referencesSources')) {
            $referencesSources = $this->referencesSources();
            foreach ($referencesSources as $rName => $sourceConfig) {
                $sources[$rName] = [];
                if (isset($sourceConfig['model'])) {
                    $model                   = $sourceConfig['model'];
                    $keyBy                   = $sourceConfig['keyBy'];
                    $human_name              = $sourceConfig['human_name'];
                    $m                       = modelNamesResolver::getModelObject($model);
                    $dataSource              = $m
                        ->q()
                        ->addSelect($keyBy)
                        ->addSelect($human_name)
                        ->orderBy($keyBy, 'ASC')
                        ->get()
                        ->keyBy($keyBy)
                        ->toArray();
                    $sources[$rName]['list'] = $dataSource;
                }
                $sources[$rName] = array_merge($sources[$rName], $sourceConfig);
            }
        }

        return $sources;
    }

    ##################################################
    public function referencesTo()
    {
        return [];
    }
    #endregion relations
    ##################################################
    #region Examples
    /**
     * Just For Example!!!
     */
    private function EXAMPLE_fields()
    {
        $className             = 'ValidatorClassName';
        $staticMethod          = 'staticMethodName';
        $externalScopeVariable = 'someValue';

        return
            [
                'fieldName' => [
                    'default'    => 'field default value on INSERT',
                    'type'       => ['string', 'number', 'etc'],
                    'filters'    => [
                        // Could be a closure, string with function name or an array
                        [$className, $staticMethod],
                        function ($value) use ($externalScopeVariable) {
                            return trim($value);
                        },
                        'trim',
                    ],
                    'validators' => [
                        [
                            // 'f' - Could be a closure, string with function name or an array
                            'f'       => 'strlen',
                            'errorIf' => [FALSE, 0],
                            'msg'     => 'Please, fill Name',
                        ],
                    ],
                ],
            ];
    }

    #rendegion Examples
    ##################################################
}
