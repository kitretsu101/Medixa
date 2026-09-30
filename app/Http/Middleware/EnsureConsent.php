<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\LabReport;
use App\Models\Prescription;
use App\Services\AuditService;
use App\Services\ConsentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsent
{
    /**
     * Map of supported resource types to their Eloquent model classes.
     *
     * @var array<string, class-string>
     */
    protected const RESOURCE_MODELS = [
        'lab_report' => LabReport::class,
        'prescription' => Prescription::class,
        'appointment' => Appointment::class,
    ];

    public function __construct(
        protected ConsentService $consentService,
        protected AuditService $auditService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication required.');
        }

        $modelClass = static::RESOURCE_MODELS[$resourceType] ?? null;

        if (! $modelClass) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid consent resource type: {$resourceType}");
        }

        $resource = null;

        // Resolve resource from route parameters
        if ($request->route()) {
            foreach ($request->route()->parameters() as $parameter) {
                if ($parameter instanceof $modelClass) {
                    $resource = $parameter;
                    break;
                }

                if (is_numeric($parameter) || is_string($parameter)) {
                    $resource = $modelClass::find($parameter);
                    if ($resource) {
                        break;
                    }
                }
            }
        }

        if (! $resource) {
            abort(Response::HTTP_NOT_FOUND, 'Resource not found for consent validation.');
        }

        // Rule: Patient accessing their OWN resource bypasses the consent check
        if ((int) $user->id === (int) $resource->patient_id) {
            return $next($request);
        }

        // Look for an active consent token for the current grantee on this resource
        $token = $this->consentService->findValidToken(
            granteeId: $user->id,
            resourceType: $resourceType,
            resourceId: $resource->id,
        );

        if (! $token || ! $this->consentService->validate($token->id)) {
            abort(Response::HTTP_FORBIDDEN, 'Access denied: Valid consent token required.');
        }

        // Successfully validated: write immutable audit log entry
        $this->auditService->log(
            actorId: $user->id,
            patientId: $resource->patient_id,
            resourceType: $resourceType,
            resourceId: $resource->id,
            consentTokenId: $token->id,
        );

        return $next($request);
    }
}
