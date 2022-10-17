<?php

namespace App\Http\Controllers\Aplication;

use App\Contract\DowngradeUserAPIInterface;
use App\Contract\FindByExternalIdInterface;
use App\Contract\UpdateUserInterface;
use App\Contract\UpgradeUserAPIInterface;
use App\DTO\DowngradeUserDTO;
use App\DTO\FindByExternalIdUserDTO;
use App\DTO\UpdateUserDTO;
use App\DTO\UpgradeUserDTO;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DowngradeUsersContoller extends Controller
{

    /**
     *
     * @var DowngradeUserAPIInterface
     */
    private DowngradeUserAPIInterface $downgrade;

    /**
     *
     * @var FindByExternalIdInterface
     */
    private FindByExternalIdInterface $user;

    /**
     *
     * @var UpdateUserInterface
     */
    private UpdateUserInterface $update;
    
    public function __construct(DowngradeUserAPIInterface $downgrade, FindByExternalIdInterface $user, UpdateUserInterface $update)
    {
        $this->downgrade = $downgrade;
        $this->user = $user;
        $this->update = $update;
    }

    /**
     *
     * @OA\Put(
     *     path="/api/user/{id}/downgrade",
     *     tags={"User"},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          example="1",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     
     *     @OA\Response(response="200", description="Store successful", @OA\JsonContent()),
     *     @OA\Response(response="413", description="Store failed", @OA\JsonContent()),
     * 
     * )
     */
    public function __invoke(Request $request, $id)
    {

        DB::beginTransaction();

        $userDTO = new DowngradeUserDTO([
            'id' => $id,
        ]);

        $findUserDTO = New FindByExternalIdUserDTO([
            'id' => $id,
        ]);

        $user = $this->user->handle($findUserDTO);

        if(!$user){
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Verifique se o id qualifica está correto.'
            ]);
        }
        
        $response = $this->downgrade->handle($userDTO);

        if(!$response['success']){
            DB::rollback();
            return response()->json($response);
        }
        
        $level = $response['data']['access_level'];
        
        $updateDTO = new UpdateUserDTO([
            'user' => $user,
            'data' => ['access_level' => (string)$level],
        ]);
        
        $update = $this->update->handle($updateDTO);
        
        if(!$update){
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'tente novamente mais tarde.'
            ]);
        }
        


        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'data' => $response,
        ], Response::HTTP_CREATED);
    }
}
