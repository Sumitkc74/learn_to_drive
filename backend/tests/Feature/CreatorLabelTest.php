<?php
namespace Tests\Feature;
use App\Models\{User,Question};
use Tests\TestCase;
class CreatorLabelTest extends TestCase
{
    public function test_self_registered_users_and_admin_created_records_have_distinct_labels(): void
    {
        $user = new User(['name'=>'Learner','role'=>'User']);
        $user->setRelation('creator', null);
        $this->view('admin.crud.partials.creator',['record'=>$user])->assertSee('Self-registered')->assertDontSee('Legacy record');
        $user->created_by=12;
        $user->setRelation('creator',new User(['name'=>'Creating admin']));
        $this->view('admin.crud.partials.creator',['record'=>$user])->assertSee('Creating admin')->assertDontSee('Self-registered');
        $user->setRelation('creator',null);
        $this->view('admin.crud.partials.creator',['record'=>$user])->assertSee('Former admin');
        $user->created_by=null;$user->is_seed_admin=true;
        $this->view('admin.crud.partials.creator',['record'=>$user])->assertSee('System account');
    }
}
