<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\tale as taleAlias;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\Data;
use alina\utils\Obj;
use alina\utils\Request;
use alina\utils\Sys;

class Tale
{
    /**
     * @route /tale/uosert
     * @route /Generic/index/test/path/parameters
     */
    public function actionUpsert($id = NULL)
    {
        $mTale  = new taleAlias();
        $vd     = (object)[
            'id'           => NULL,
            'form_id'      => __FUNCTION__,
            'header'       => '***',
            'body'         => 'text',
            'publish_at'   => ALINA_TIME,
            'is_submitted' => 0,
        ];
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if (empty($id)) {
            if ($isPost) {
                if (isset($post->id)) {
                    $id = $post->id;
                }
            }
        }
        ########################################
        if ($id) {
            $mTaleAttrs = $mTale->getById($id);
        } else {
            $mTaleAttrs = $mTale->getOne(['is_submitted' => 0, 'owner_id' => CurrentUser::obj()->id,]);
            if (!$mTaleAttrs->id) {
                $mTaleAttrs = $mTale->insert($vd);
                Message::set('Inserted new tale');
                Sys::redirect("/tale/upsert/{$mTale->id}", 307);
            }
        }
        if ($isPost) {
            $vd = Data::mergeObjects(
                $vd,
                $mTaleAttrs,
                Data::deleteEmptyProps($post)
            );
            if (
                AlinaAccessIfOwner($vd->owner_id)
                ||
                AlinaAccessIfAdmin()
                ||
                AlinaAccessIfModerator()
            ) {
                $vd->is_submitted = 1;
                $mTaleAttrs       = $mTale->updateById($vd);
                Message::setInfo('Success');
            } else {
                Message::setDanger('Edit of tale is not allowed');
            }
        }
        $vd = Data::mergeObjects($vd, $mTaleAttrs);
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    ########################################
    public function actionDelete($id = NULL)
    {
        $vd     = (object)[
            'form_id' => __FUNCTION__,
            'success' => 0,
        ];
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if ($isPost && $id && (
                AlinaAccessIfOwner($post->owner_id)
                ||
                AlinaAccessIfAdmin()
                ||
                AlinaAccessIfModerator()
            )) {
            $vd->comments1 = (new taleAlias())->delete(['root_tale_id' => $id,]);
            $vd->comments3 = (new taleAlias())->delete(['answer_to_tale_id' => $id,]);
            $vd->rows      = (new taleAlias())->deleteById($id);
            Message::set('Deleted');
            $vd->success = 1;
        } else {
            Message::setDanger('Failed');
        }
        ########################################
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    ########################################
    ########################################
    ########################################
    /**
     * @param int $pageSze
     * @param int $page
     * @param array $answer_to_tale_ids
     * @route /tale/feed
     * @route /tale/feed/5/1/125
     */
    public function actionFeed($pageSze = 5, $page = 1, $answer_to_tale_ids = [])
    {
        $vd = (object)[
            'tale' => [],
        ];
        ########################################
        $conditions[] = ["tale.is_submitted", '=', 1];
        $conditions[] = ["tale.publish_at", '<=', ALINA_TIME];
        if (empty($answer_to_tale_ids)) {
            $conditions[] = ["tale.type", '=', 'POST'];
            $sort[]       = ["tale.publish_at", 'DESC'];
        } else {
            $sort[] = ["tale.publish_at", 'ASC'];
        }
        $collection = $this->processResponse($conditions, $sort, $pageSze, $page, $answer_to_tale_ids);
        ########################################
        $vd->tale = $collection->toArray();
        ########################################
        echo (new htmlAlias)->page($vd);
    }

    ########################################
    protected function processResponse($conditions = [], $sort = [], $pageSize = 5, $pageCurrentNumber = 1, $answer_to_tale_ids = [])
    {
        $mTale = new taleAlias();
        $q     = $mTale->getAllWithReferencesPart1($conditions);
        if (!empty($answer_to_tale_ids)) {
            if (!is_array($answer_to_tale_ids)) {
                $answer_to_tale_ids = [$answer_to_tale_ids];
            }
            $q->whereIn('tale.answer_to_tale_id', $answer_to_tale_ids);
        }
        $collection = $mTale->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber);

        return $collection;
    }
    ########################################
    ########################################
    ########################################
    public function actionCommentAdd()
    {
        //ToDo: Checks if allowed to comment etc
        $mTale              = new taleAlias();
        $post               = Request::obj()->POST;
        $post->is_submitted = 1;
        $mTale->insert($post);
        $vd = $this->getTaleComments($post->answer_to_tale_id);
        echo (new jsonView())->standardRestApiResponse($vd);
    }
    ########################################
    ########################################
    ########################################
    public function getTaleComments($answer_to_tale_ids = [], $level = 1, $limit = 10, $offset = 0)
    {
        if (!is_array($answer_to_tale_ids)) {
            $answer_to_tale_ids = [$answer_to_tale_ids];
        }
        $mTaleAsComment = new taleAlias();
        $res            = $mTaleAsComment
            ->q()
            ->select([
                'tale.*',
                'owner.firstname AS owner_firstname',
                'owner.lastname AS owner_lastname',
                'owner.emblem AS owner_emblem',
            ])
            ->whereIn('tale.answer_to_tale_id', $answer_to_tale_ids)
            ->where('tale.level', $level)
            ->where('tale.is_submitted', 1)
            ->leftJoin('user as owner', 'tale.owner_id', '=', 'owner.id')
            ->limit($limit)
            ->offset($offset)
            ->orderBy('tale.publish_at', 'ASC')
            ->get();;

        return $res;
    }

    ########################################
    ########################################
    ########################################
}
