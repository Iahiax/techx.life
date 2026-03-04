Route::middleware('auth:api')->group(function () {
    Route::apiResource('contracts', Api\ContractController::class);
    Route::get('deductions', [Api\DeductionController::class, 'index']);
    Route::post('webhook/lean', [Api\WebhookController::class, 'handleLean']);
});
