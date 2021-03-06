<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\db\Query;

/**
 * UserController implements the CRUD actions for User model.
 */
class AddressController extends Controller
{


    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                ]
            ]
        ];
    }


    public function beforeAction($event)
    {
        $action = $event->id;
        if (isset($this->actions[$action])) {
            $verbs = $this->actions[$action];
        } elseif (isset($this->actions['*'])) {
            $verbs = $this->actions['*'];
        } else {
            return $event->isValid;
        }

        $verb = Yii::$app->getRequest()->getMethod();

        $allowed = array_map('strtoupper', $verbs);

        if (!in_array($verb, $allowed)) {
            $this->setHeader(400);
            echo json_encode(['status' => 0, 'error_code' => 400, 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            exit;
        }

        return true;
    }


    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {

        $params = $_REQUEST;

        $filter = [];
        $houseRangeFilter = [];
        $datetimeFilter = [];
        $sort = "";
        $page = 1;
        $limit = 100;

        if (isset($params['page']) && is_numeric($params['page'])) {
            $page = $params['page'];
        }

        if (isset($params['size']) && is_numeric($params['size']) && $params['size'] <= 200) {
            $limit = $params['size'];
        }
        $offset = $limit * ($page - 1);


        /* Filter elements */
        if (isset($params['search'])) {
            $filter = (array)json_decode($params['search']) + ['house' => null, 'datetime' => null];
            $houseRangeFilter = (array)$filter['house'];
            $datetimeFilter = (array)$filter['datetime'];
        }

        if (isset($params['sort']) && preg_match('/^(\w+)$/ui', $params['sort'])) {
            $sort = $params['sort'];
            if ($params['reverse'] == 'false') {
                $sort .= ' ASC';
            } else {
                $sort .= ' DESC';
            }
        }

        $filter = $filter + ['id' => null, 'country' => null, 'city' => null, 'street' => null, 'postcode' => null];
        $houseRangeFilter = $houseRangeFilter + ['lower' => null, 'higher' => null];
        $datetimeFilter = $datetimeFilter + ['before' => null, 'after' => null];

        $query = new Query;
        $query->offset($offset)
            ->limit($limit)
            ->from('address')
            ->andFilterWhere(['=', 'id', $filter['id']])
            ->andFilterWhere(['like', 'country', $filter['country']])
            ->andFilterWhere(['like', 'city', $filter['city']])
            ->andFilterWhere(['like', 'street', $filter['street']])
            ->andFilterWhere(['<=', 'house', $houseRangeFilter['lower']])
            ->andFilterWhere(['>=', 'house', $houseRangeFilter['higher']])
            ->andFilterWhere(['like', 'postcode', $filter['postcode']])
            ->andFilterWhere(['<=', 'datetime', $datetimeFilter['before']])
            ->andFilterWhere(['>=', 'datetime', $datetimeFilter['after']])
            ->orderBy($sort)
            ->select('*');

        $command = $query->createCommand();
        $models = $command->queryAll();
        $totalPages = $query->count();
        $totalPages = ceil($totalPages / $limit);

        $this->setHeader(200);
        echo json_encode(['content' => $models, 'totalPages' => $totalPages], JSON_PRETTY_PRINT);
    }


    private function setHeader($status)
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status);
        $content_type = "application/json; charset=utf-8";

        header($status_header);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Content-type: ' . $content_type);
    }


    private function getStatusCodeMessage($status)
    {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        ];
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}
