<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MemberDocument;
use App\Models\User;

class MemberDocumentPolicy extends TenantPolicy
{
    public function view(User $user, MemberDocument $document): bool
    {
        return $this->allows($user, $document, Permission::MembersManage);
    }

    public function delete(User $user, MemberDocument $document): bool
    {
        return $this->allows($user, $document, Permission::MembersManage);
    }
}
