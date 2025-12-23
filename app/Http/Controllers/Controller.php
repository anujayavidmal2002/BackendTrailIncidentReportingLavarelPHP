<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *    title="Trail Incident Reporting API",
 *    version="1.0.0",
 *    description="API for reporting and managing trail incidents",
 *    @OA\Contact(
 *        email="support@trailincidents.com",
 *        name="Trail Incidents Support"
 *    ),
 *    @OA\License(
 *        name="MIT",
 *        url="https://opensource.org/licenses/MIT"
 *    )
 * ),
 * @OA\Server(
 *    url="http://localhost:8000/api",
 *    description="Development Server"
 * ),
 * @OA\SecurityScheme(
 *    type="http",
 *    description="Login with username and password to get the authentication token",
 *    name="Token based security",
 *    in="header",
 *    scheme="bearer",
 *    securityScheme="bearer"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
