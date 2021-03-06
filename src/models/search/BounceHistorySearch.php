<?php
namespace strong2much\bounce\models\search;

use Yii;
use yii\data\ActiveDataProvider;
use strong2much\bounce\models\BounceHistory;

/**
 * BounceHistorySearch represents the model behind the search form about `strong2much\bounce\models\BounceHistory`.
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class BounceHistorySearch extends BounceHistory
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['is_critical', 'boolean'],
            [['email','reason','status','type'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     * @param array $params
     * @param string $email for history search
     * @return ActiveDataProvider
     */
    public function search($params, $email)
    {
        $query = BounceHistory::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['time' => SORT_DESC],
            ],
            'pagination' => false,
        ]);

        $this->load($params);
        $this->email = $email;

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'is_critical' => $this->is_critical,
        ]);

        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'reason', $this->reason])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'type', $this->type]);

        return $dataProvider;
    }
}