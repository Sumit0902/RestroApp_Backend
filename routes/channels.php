<?php 

use App\Models\User;


Broadcast::channel('company.{companyId}.managers', function (User $user, $companyId) {
    return $user->company->id == $companyId ;
});
