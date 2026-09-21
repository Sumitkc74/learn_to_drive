<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
class AccountMfa {
    public function enable(User $user,string $secret,string $code,?int $keepToken=null): array {
        return DB::transaction(function()use($user,$secret,$code,$keepToken){
            $locked=User::lockForUpdate()->findOrFail($user->id);
            abort_if((bool)$locked->mfa_secret,409,'MFA is already enabled.');
            $step=(new Google2FA)->verifyKeyNewer($secret,$code,0,1);
            if($step===false)throw \Illuminate\Validation\ValidationException::withMessages(['code'=>'The code was not accepted.']);
            $codes=collect(range(1,8))->map(fn()=>bin2hex(random_bytes(8)))->all();
            $locked->forceFill(['mfa_secret'=>$secret,'mfa_last_step'=>$step,'mfa_recovery_codes'=>array_map(fn($c)=>hash('sha256',$c),$codes)])->save();
            $locked->tokens()->when($keepToken,fn($query)=>$query->where('id','!=',$keepToken))->delete();
            $user->refresh();
            return $codes;
        });
    }
    public function disable(User $user,string $code): bool {
        return DB::transaction(function()use($user,$code){
            $locked=User::lockForUpdate()->findOrFail($user->id);
            if(!$this->consume($locked,$code))return false;
            $locked->forceFill(['mfa_secret'=>null,'mfa_recovery_codes'=>null,'mfa_last_step'=>null])->save();
            $user->refresh();
            return true;
        });
    }
    public static function marker(User $user): string { return $user->id.':'.hash('sha256',(string)$user->mfa_secret); }
    public function consume(User $user,string $code): bool {
        return DB::transaction(function()use($user,$code){
            $locked=User::lockForUpdate()->findOrFail($user->id);
            if(!$locked->mfa_secret) return false;
            if(preg_match('/^\d{6}$/',$code)) {
                $step=(new Google2FA)->verifyKeyNewer($locked->mfa_secret,$code,$locked->mfa_last_step ?? 0,1);
                if($step!==false){$locked->forceFill(['mfa_last_step'=>$step])->save();return true;}
            }
            $codes=$locked->mfa_recovery_codes ?? [];
            foreach($codes as $index=>$hash)if(hash_equals($hash,hash('sha256',$code))){
                unset($codes[$index]);$locked->forceFill(['mfa_recovery_codes'=>array_values($codes)])->save();return true;
            }
            return false;
        });
    }
}
