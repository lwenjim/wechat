<?php

namespace App\Transformers;

use App\Models\User;
use App\Models\WechatUser;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['group', 'addresses', 'carts', 'coins', 'kefus', 'tags', 'msgs', 'walks', 'signs', 'likes', 'invites', 'orders', 'tasks', 'wechats', 'coupon_items', 'WechatUser', 'phones', 'MiniUser', 'diamond'];

    public function transform(User $user)
    {
        return $user->attributesToArray();
    }

    public function includeGroup(User $user)
    {
        $group = $user->group()->select('id', 'name')->first();
        if ($group) {
            return $this->item($group, new GroupTransformer());
        }
    }

    public function includeAddresses(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $signs = $user->addresses()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->addresses()->count();
        return $this->collection($signs, new UserAddressTransformer())->setMeta(['total' => $total]);
    }

    public function includeCarts(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $carts = $user->carts()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->carts()->count();
        return $this->collection($carts, new UserCartTransformer())->setMeta(['total' => $total]);
    }

    public function includeTasks(User $user, ParamBag $params = null)
    {
        $orderField = null;
        if ($params->get('order')) {
            list($orderBy, $orderType) = $params->get('order');
            if ($orderBy == 'sort' || $orderBy == 'star') {
                $orderField = $orderBy;
                $orderBy = 'created_at';
            }
        } else {
            list($orderBy, $orderType) = ['created_at', 'desc'];
        }
        $tasks = $user->tasks()->where('parent_id', 0)->orderBy($orderBy, $orderType)->get()->map(function ($item) {
            $item->pivot = ['task_id' => $item->pivot->task_id, 'owner' => $item->pivot->owner, 'remind' => json_decode($item->pivot->remind, true), 'sort' => $item->pivot->sort, 'star' => $item->pivot->star];
            return $item;
        });
        if ($orderField) {
            $sortArr1 = collect([]);
            $sortArr0 = collect([]);
            foreach ($tasks as $task) {
                if ($task->pivot[$orderField] == 1) {
                    $sortArr1->push($task);
                } else {
                    $sortArr0->push($task);
                }
            }
            if ($orderType == 'asc') {
                $tasks0 = $sortArr0->sortBy(function ($item) {
                    return $item->pivot['task_id'];
                });
                if ($sortArr1->isNotEmpty()) {
                    $tasks1 = $sortArr1->sortBy(function ($item) {
                        return $item->pivot['task_id'];
                    });
                    $tasks = $tasks0->merge($tasks1);
                } else {
                    $tasks = $tasks0;
                }
            } else {
                $tasks0 = $sortArr0->sortByDesc(function ($item) {
                    return $item->pivot['task_id'];
                });
                if ($sortArr1->isNotEmpty()) {
                    $tasks1 = $sortArr1->sortByDesc(function ($item) {
                        return $item->pivot['task_id'];
                    });
                    $tasks = $tasks1->merge($tasks0);
                } else {
                    $tasks = $tasks0;
                }
            }
        }
        return $this->collection($tasks, new TaskTransformer())->setMeta(['total' => $tasks->count()]);
    }

    public function includeWechatUser(User $user)
    {
        return $this->item($user->wechatUser()->first(), new WechatUserTransformer());
    }

    public function includeInvites(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $signs = $user->invites()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->invites()->count();
        return $this->collection($signs, new UserInviteTransformer())->setMeta(['total' => $total]);
    }

    public function includeLikes(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $signs = $user->likes()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->likes()->count();
        return $this->collection($signs, new UserLikeTransformer())->setMeta(['total' => $total]);
    }

    public function includeCoins(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $coins = $user->coins()->select('id', 'number', 'remark', 'created_at')->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->coins()->count();
        return $this->collection($coins, new UserCoinTransformer())->setMeta(['total' => $total]);
    }

    public function includeDiamond(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $blueDiamond = $user->blueDiamond()->select('id', 'number', 'remark', 'created_at')->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->blueDiamond()->count();
        return $this->collection($blueDiamond, new UserDiamondTransformer())->setMeta(['total' => $total]);
    }

    public function includeWalks(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $user->walks()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->walks()->count();
        return $this->collection($walks, new UserWalkTransformer())->setMeta(['total' => $total]);
    }

    public function includeSigns(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $signs = $user->signs()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->signs()->count();
        return $this->collection($signs, new UserSignTransformer())->setMeta(['total' => $total]);
    }

    public function includeTags(User $user)
    {
        return $this->collection($user->tags()->select('id', 'name', 'image', 'active')->where('status', 1)->orderBy('sort', 'asc')->get(), new TagTransformer());
    }

    public function includeKefus(User $user, ParamBag $params = null)
    {
        if ($params->get('id')) {
            $id = (array)$params->get('id');
            $id = (string)current($id);
        } else {
            $id = 0;
        }
        return $this->collection($user->kefus()->select('id', 'name', 'image')->when($id, function ($query) use ($id) {
            return $query->where('id', $id);
        })->where('status', 1)->get(), new KefuTransformer());
    }

    public function includeOrders(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $orders = $user->orders()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->orders()->count();
        return $this->collection($orders, new UserOrderTransformer())->setMeta(['total' => $total]);
    }

    public function includePhones(User $user)
    {
        return $this->collection($user->phones, new UserPhoneTransformer());
    }

    public function includeMsgs(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $type_array['shop'] = ['order'];
        $type_count = [];
        foreach ($type_array as $key => $val) {
            $type_count[$key] = $user->msgs()->whereIn('type', $val)->where('is_read', 0)->count();
        }
        if ($params->get('type')) {
            $type = (array)$params->get('type');
            $type = (string)current($type);
        } else {
            $type = false;
        }
        if (!$params->get('read')) {
            $user->msgs()->when($type, function ($query) use ($type, $type_array) {
                return $query->whereIn('type', $type_array[$type]);
            })->where('is_read', 0)->update(['is_read' => 1]);
        }
        $msgs = $user->msgs()->when($type, function ($query) use ($type, $type_array) {
            return $query->whereIn('type', $type_array[$type]);
        })->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->msgs()->when($type, function ($query) use ($type, $type_array) {
            return $query->whereIn('type', $type_array[$type]);
        })->count();
        return $this->collection($msgs, new UserMsgTransformer())->setMeta(['total' => $total, 'unread' => $type_count]);
    }

    public function includeWechats(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $user->wechats()->select('id', 'name', 'wechat', 'headimgurl')->orderBy('created_at', 'desc')->take($row)->skip($offset)->get()->map(function ($item) {
            $item->pivot = ['is_default' => $item->pivot->is_default, 'subscribe' => $item->pivot->subscribe];
            return $item;
        });
        $total = $user->wechats()->count();
        return $this->collection($walks, new WechatTransformer())->setMeta(['total' => $total]);
    }

    public function includeCouponItems(User $user, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $items = $user->coupon_items()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $user->coupon_items()->count();
        return $this->collection($items, new CouponItemTransformer())->setMeta(['total' => $total]);
    }

    public function includeMiniUser(User $user)
    {
        if ($user->mini_user_id <= 0) {
            return null;
        }
        return $this->item(User::select('blue_diamond','coin')->find($user->mini_user_id), new UserTransformer());
    }
}
