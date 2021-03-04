<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Libraries;

use App\Models\Beatmap;
use App\Models\BeatmapDiscussion;
use App\Models\User;
use App\Traits\Memoizes;
use App\Transformers\BeatmapDiscussionTransformer;
use App\Transformers\BeatmapTransformer;
use App\Transformers\UserCompactTransformer;
use Illuminate\Pagination\Paginator;

class BeatmapsetDiscussionsBundle extends BeatmapsetDiscussionsBundleBase
{
    use Memoizes;

    private const DISCUSSION_WITHS = ['beatmapDiscussionVotes', 'beatmapset', 'startingPost'];

    public function getData()
    {
        return $this->getDiscussions();
    }

    public function toArray()
    {
        // TODO: beatmapset nested include should be removed (should be moved to side load);
        // currently left here as some components assume beatmapset is always nested.
        static $discussionIncludes = ['starting_post', 'beatmapset', 'current_user_attributes'];

        return [
            'beatmaps' => json_collection($this->getBeatmaps(), new BeatmapTransformer()),
            'cursor' => $this->getCursor(),
            'discussions' => json_collection($this->getDiscussions(), new BeatmapDiscussionTransformer(), $discussionIncludes),
            'included_discussions' => json_collection($this->getRelatedDiscussions(), new BeatmapDiscussionTransformer(), $discussionIncludes),
            'reviews_config' => BeatmapsetDiscussionReview::config(),
            'users' => json_collection($this->getUsers(), new UserCompactTransformer(), ['groups']),
        ];
    }

    private function getBeatmaps()
    {
        return $this->memoize(__FUNCTION__, function () {
            // using all beatmaps of the beatmapsets for the beatmap selector when editing.
            $beatmapsetIds = $this->getDiscussions()->pluck('beatmapset_id')->unique()->values();

            return Beatmap::whereIn('beatmapset_id', $beatmapsetIds)->get();
        });
    }

    private function getDiscussions()
    {
        return $this->memoize(__FUNCTION__, function () {
            $this->search = BeatmapDiscussion::search($this->params);

            $query = $this->search['query']->with(static::DISCUSSION_WITHS)->limit($this->search['params']['limit'] + 1);

            $this->paginator = new Paginator(
                $query->get(),
                $this->search['params']['limit'],
                $this->search['params']['page'],
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'query' => $this->search['params'],
                ]
            );

            return $this->paginator->getCollection();
        });
    }

    private function getRelatedDiscussions()
    {
        return $this->memoize(__FUNCTION__, function () {
            return BeatmapDiscussion::whereIn('parent_id', $this->getDiscussions()->pluck('id'))->with(static::DISCUSSION_WITHS)->get();
        });
    }

    private function getUsers()
    {
        return $this->memoize(__FUNCTION__, function () {
            $discussions = $this->getDiscussions();

            $allDiscussions = $discussions->merge($this->getRelatedDiscussions($discussions));
            $userIds = $allDiscussions->pluck('user_id')->merge($allDiscussions->pluck('startingPost.last_editor_id'))->unique()->values();

            $users = User::whereIn('user_id', $userIds)->with('userGroups');

            if (!$this->isModerator) {
                $users->default();
            }

            return $users->get();
        });
    }
}
