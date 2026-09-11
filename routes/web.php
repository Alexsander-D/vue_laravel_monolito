<?php

use App\Http\Controllers\Registration\AccessControlController;
use App\Http\Controllers\Registration\ComponentsDefectsAndSolutionsController;
use App\Http\Controllers\Registration\ProductsController;
use App\Http\Controllers\External\CalendarController;
use App\Http\Controllers\External\CustomersViewController;
use App\Http\Controllers\External\CustomersController;
use App\Http\Controllers\External\FinalReportController;
use App\Http\Controllers\External\IncludeController;
use App\Http\Controllers\External\PositioningController;
use App\Http\Controllers\External\PrintReportController;
use App\Http\Controllers\External\ProductEntryController;
use App\Http\Controllers\External\ProductivityReportController;
use App\Http\Controllers\External\SchedulingController;
use App\Http\Controllers\External\ScreeningApprovalController;
use App\Http\Controllers\External\ScreeningAssignmentController;
use App\Http\Controllers\External\ViewScreeningController;
use App\Http\Controllers\External\ScreeningController;
use App\Http\Controllers\External\ScreeningDefectsSolutionController;
use App\Http\Controllers\External\ScreeningTimelineController;
use App\Http\Controllers\External\TechLogController;
use App\Http\Controllers\External\ViewMaterialController;
use App\Http\Controllers\External\StatusController;
use App\Http\Controllers\External\ViewProductivityController;
use App\Http\Controllers\Internal\AnalyzeController;
use App\Http\Controllers\Internal\ExcelUploadController;
use App\Http\Controllers\Internal\CollectTrackingController;
use App\Http\Controllers\Internal\SeparatedTrackingController;
use App\Http\Controllers\Internal\EntryController;
use App\Http\Controllers\Internal\IndividualAnalyzeController;
use App\Http\Controllers\Internal\SetQueueController;
use App\Http\Controllers\Internal\QueueController;
use App\Http\Controllers\Internal\ViewReportController;
use App\Http\Controllers\Internal\IndividualViewReportController;
use App\Http\Controllers\Internal\TimelineController;
use App\Http\Controllers\Internal\ProductOutputController;
use App\Http\Controllers\Internal\ProductTransferController;
use App\Http\Controllers\Internal\ProductTransferControllerAdmin;
use App\Http\Controllers\StockController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'welcomeImage' => asset('images/welcome.png'),
    ]);
})->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])
    ->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        Route::post(
            '/attendance/store',
            [AttendanceController::class, 'store']
        )->name('attendance.store');

        Route::get(
            '/attendance/report',
            [AttendanceController::class, 'report']
        )->name('attendance.report');

        Route::post(
            '/attendance/send-productivity-report',
            [AttendanceController::class, 'sendProductivityReport']
        )->name('attendance.sendProductivityReport');

        Route::put(
            '/attendance/{attendance}',
            [AttendanceController::class, 'update']
        )->name('attendance.update');

        Route::delete(
            '/attendance/{attendance}',
            [AttendanceController::class, 'destroy']
        )->name('attendance.destroy');

        Route::get('/attendance/stock', [StockController::class, 'index'])->name('stock.index');
        Route::post('/attendance/stock/create', [StockController::class, 'create'])->name('stock.create');
        Route::put('/attendance/stock/{stock}', [StockController::class, 'update'])->name('stock.update');
        Route::put('/attendance/stock/{stock}/sell', [StockController::class, 'sell'])->name('stock.sell');
        Route::delete('/attendance/stock/{stock}', [StockController::class, 'destroy'])->name('stock.destroy');

        Route::prefix('technical_assistance')->group(function () {
            Route::prefix('registration')->group(function () {
                // ? FAILURES
                Route::get('/failures', [ComponentsDefectsAndSolutionsController::class, 'index'])->name('failures.index');
                Route::post('/components/create', [ComponentsDefectsAndSolutionsController::class, 'create'])->name('failure.create');
                Route::post('/components/store', [ComponentsDefectsAndSolutionsController::class, 'store'])->name('components.store');

                Route::post('/find/products', [ProductsController::class, 'show'])->name('findProducts.show');
                Route::post('/find/products/byUser', [ProductsController::class, 'byUser'])->name('findProducts.byUser');
                Route::post('/find/products/byAdmin', [ProductsController::class, 'byAdmin'])->name('findProducts.byAdmin');

                Route::post('/find/family', [ComponentsDefectsAndSolutionsController::class, 'findFamily'])->name('findFamily.show');
                Route::post('/find/components_by_family', [ComponentsDefectsAndSolutionsController::class, 'findComponentsByFamily'])->name('findComponentsByFamily.show');
                Route::post('/find/defects', [ComponentsDefectsAndSolutionsController::class, 'findDefects'])->name('findDefects.show');
                Route::post('/find/solutions', [ComponentsDefectsAndSolutionsController::class, 'findSolutions'])->name('findSolutions.show');

                // ? PRODUCTS
                Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
                Route::post('/products/create', [ProductsController::class, 'create'])->name('products.create');
                Route::post('/products/update', [ProductsController::class, 'update'])->name('products.update');
                Route::get('/products/datatable', [ProductsController::class, 'datatable'])->name('products.datatable');
                Route::get('/products/datatable/export', [ProductsController::class, 'export'])->name('products.export');

                // ? ACCESS CONTROL
                Route::get('/access_control', [AccessControlController::class, 'index'])->name('access-control.index');
                Route::post('/access_control/create', [AccessControlController::class, 'create'])->name('access-team-members.create');
                Route::get('/teams/{team}/roles', [AccessControlController::class, 'show'])->name('teams.roles');
            });

            Route::prefix('internal')->group(function () {
                Route::get('/separated_tracking', [SeparatedTrackingController::class, 'index'])->name('separated-tracking.index');
                Route::get('/separated_tracking/datatable', [SeparatedTrackingController::class, 'datatable'])->name('separated-tracking.datatable');
                Route::post('/separated_tracking/create', [SeparatedTrackingController::class, 'create'])->name('separated-tracking.create');
                Route::put('/separated_tracking/{id}', [SeparatedTrackingController::class, 'update'])->name('separated-tracking.update');
                Route::delete('/separated_tracking/{id}', [SeparatedTrackingController::class, 'delete'])->name('separated-tracking.delete');
                Route::post('/separated_tracking/excel', [SeparatedTrackingController::class, 'excel'])->name('separated-tracking.excel');

                Route::get('/collect_tracking', [CollectTrackingController::class, 'index'])->name('collect-tracking.index');
                Route::post('/collect_tracking/create', [CollectTrackingController::class, 'create'])->name('collect-tracking.create');
                Route::get('/collect_tracking/show', [CollectTrackingController::class, 'show'])->name('collect-tracking.show');

                Route::get('/entry', [EntryController::class, 'index'])->name('entry.index');
                Route::post('/entry/create', [EntryController::class, 'create'])->name('entry.create');
                Route::delete('/entry/destroy/{id}', [EntryController::class, 'destroy'])->name('entry.destroy');

                Route::get('/set_queue', [SetQueueController::class, 'index'])->name('set-queue.index');
                Route::get('/set_queue/datatable', [SetQueueController::class, 'datatable'])->name('set-queue.datatable');
                // Route::get('/set_queue/{userId?}', [SetQueueController::class, 'show'])->name('set-queue.show');
                Route::post('/set_queue/create', [SetQueueController::class, 'create'])->name('set-queue.create');
                Route::delete('/set_queue/{id}', [SetQueueController::class, 'destroy'])->name('set-queue.delete');

                Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
                Route::get('/queue/{queueId}', [QueueController::class, 'show'])->name('queue.show');
                Route::put('/queue/update', [QueueController::class, 'update'])->name('queue.update');

                Route::get('/analyzes', [AnalyzeController::class, 'index'])->name('analyzes.index');
                Route::get('/analyzes/{queueId}', [AnalyzeController::class, 'show'])->name('analyzes.show');
                Route::post('/analyzes/create', [AnalyzeController::class, 'create'])->name('analyzes.create');

                Route::get('/analyzes_report', [IndividualAnalyzeController::class, 'index'])->name('analyzes_report.index');
                Route::get('/analyzes_report/datatable', [IndividualAnalyzeController::class, 'datatable'])->name('analyzes_report.datatable');
                Route::get('/analyzes_report/export', [IndividualAnalyzeController::class, 'export'])->name('analyzes_report.export');

                Route::get('/view_report', [ViewReportController::class, 'index'])->name('report.index');
                Route::get('/view_report/datatable', [ViewReportController::class, 'datatable'])->name('report.datatable');
                Route::get('/view_report/export/{table}/{startDate}/{endDate}', [ViewReportController::class, 'export'])->name('report.export');
                Route::get('/individual_view_report', [IndividualViewReportController::class, 'index'])->name('individual_report.index');
                Route::get('/individual_view_report/datatable', [IndividualViewReportController::class, 'datatable'])->name('individual_report.datatable');
                Route::get('/individual_view_report/export/{startDate}/{endDate}', [IndividualViewReportController::class, 'export'])->name('individual_report.export');

                Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline.index');
                Route::get('/timeline/{protocolo}', [TimelineController::class, 'show'])->name('timeline.show');

                Route::get('/product_output', [ProductOutputController::class, 'index'])->name('product_output.index');
                Route::post('/product_output/create', [ProductOutputController::class, 'create'])->name('product_output.create');
                Route::get('/product_output/datatable/{responsableId?}', [ProductOutputController::class, 'datatable'])->name('product_output.datatable');

                Route::get('/product_transfer', [ProductTransferController::class, 'index'])->name('product_transfer.index');
                Route::post('/product_transfer/create', [ProductTransferController::class, 'create'])->name('product_transfer.create');
                Route::get('/product_transfer/update/{id}', [ProductTransferController::class, 'update'])->name('product_transfer.update');
                Route::get('/product_transfer/datatable', [ProductTransferController::class, 'datatable'])->name('product_transfer.datatable');

                Route::get('/product_transfer/admin', [ProductTransferControllerAdmin::class, 'index'])->name('product_transfer_admin.index');
                Route::post('/product_transfer/admin/create', [ProductTransferControllerAdmin::class, 'create'])->name('product_transfer_admin.create');
            });

            Route::prefix('external')->group(function () {
                // ? SCREENING TIMELINE 
                Route::get('/timeline', [ScreeningTimelineController::class, 'index'])->name('screeningTimeline.index');
                Route::get('/timeline/{screening_id}', [ScreeningTimelineController::class, 'show'])->name('screeningTimeline.show');

                // ? CLIENTS
                Route::get('/customers/show', [CustomersController::class, 'show'])->name('customers.show');
                Route::get('/customers/{customerId}', [CustomersController::class, 'show'])->name('customer.show');
                Route::post('/customers/create', [CustomersController::class, 'create'])->name('customers.create');
                Route::put('/customers/update', [CustomersController::class, 'update'])->name('customers.update');
                Route::get('/customers/viewCustomers/show', [CustomersViewController::class, 'show'])->name('viewCustomers.show');
                Route::get('/external/customers/datatable', [CustomersViewController::class, 'datatable'])->name('viewCustomers.datatable');
                Route::get('/external/customers/export', [CustomersViewController::class, 'export'])->name('viewCustomers.export');

                // ? SCREENING
                Route::get('/screening/index', [ScreeningController::class, 'index'])->name('screening.index');
                Route::post('/screening/create', [ScreeningController::class, 'create'])->name('screening.create');
                Route::get('/screening/show/{customerId}', [ScreeningController::class, 'show'])->name('screening.show');

                // ? VIEW SCREENING
                Route::get('/view_screening/index', [ViewScreeningController::class, 'index'])->name('ViewScreening.index');
                Route::get('/view_screening/index/{status?}', [ViewScreeningController::class, 'index'])->name('ViewScreeningStatus.index');
                Route::get('/view_screening/datatable', [ViewScreeningController::class, 'datatable'])->name('ViewScreening.datatable');
                Route::get('/view_screening/export', [ViewScreeningController::class, 'export'])->name('ViewScreening.export');

                // ? PRODUCT ENTRY
                Route::get('/products/entry/{screeningId}', [ProductEntryController::class, 'index'])->name('ProductEntry.index');
                Route::post('/products/entry/create', [ProductEntryController::class, 'create'])->name('ProductEntry.create');
                Route::delete('/products/entry/delete/{entryId}', [ProductEntryController::class, 'destroy'])->name('ProductEntry.destroy');
                Route::delete('/products/entry/delete/all/{screeningId}', [ProductEntryController::class, 'destroyAll'])->name('ProductEntry.destroyAll');
                Route::post('/products/excel', [ProductEntryController::class, 'excel'])->name('ProductEntry.excel');
                Route::patch('/products/screenings/{id}/finalize', [ProductEntryController::class, 'finalize'])->name('Screening.finalize');
                Route::post('/products/upload_excel', [ExcelUploadController::class, 'upload'])->name('excel.upload');
                Route::get('/products/entry/datatable/{screeningId}', [ProductEntryController::class, 'datatable'])->name('ProductEntry.datatable');
                Route::get('/products/entry/export/{screeningId}', [ProductEntryController::class, 'export'])->name('ProductEntry.export');

                // ? SCHEDULING
                Route::get('/scheduling/{screeningId}', [SchedulingController::class, 'index'])->name('customers.scheduling');
                Route::post('/scheduling/show', [SchedulingController::class, 'show'])->name('customers.scheduling.show');
                Route::delete('/scheduling/delete', [SchedulingController::class, 'destroy'])->name('customers.scheduling.delete');
                Route::post('/scheduling/save', [SchedulingController::class, 'store'])->name('customers.scheduling.save');

                // ? FINAL REPORT
                Route::get('/final_report/index/{screeningId}', [FinalReportController::class, 'index'])->name('customers.finalReport');
                Route::get('/final_report/datatable/{screeningId}', [FinalReportController::class, 'datatable'])->name('finalReport.datatable');
                Route::get('/final_report/export/{screeningId}', [FinalReportController::class, 'export'])->name('finalReport.export');
                Route::get('/final-report/{screeningId}/products-datatable', [FinalReportController::class, 'datatableProducts'])->name('finalReport.products.datatable');
                Route::get('/final-report/export-products/{screeningId}', [FinalReportController::class, 'exportProducts'])->name('finalReport.products.export');
                Route::get('/print-report/{screeningId}', [PrintReportController::class, 'printView'])->name('finalReport.printreport');

                Route::post('/final_report/cancel_screening', [StatusController::class, 'cancelScreening'])->name('customers.finalReport.cancel');
                Route::put('/final_report/update_status', [StatusController::class, 'updateStatus'])->name('customers.finalReport.updateStatus');
                Route::put('/final_report/update_status_no_material_check', [StatusController::class, 'updateStatusWithoutMaterialCheck'])->name('customers.finalReport.updateStatusNoMaterialCheck');

                Route::put('/final_report/update_technician', [ScreeningAssignmentController::class, 'updateTechnician'])->name('customers.finalReport.updateTechnician');
                Route::delete('/final_report/remove_technician/{id}', [ScreeningAssignmentController::class, 'removeTechnician'])->name('customers.finalReport.removeTechnician');

                Route::post('/final_report/approve', [ScreeningApprovalController::class, 'approve'])->name('customers.finalReport.approve');
                Route::post('/final_report/reprove', [ScreeningApprovalController::class, 'reprove'])->name('customers.finalReport.reprove');
                Route::post('/final_report/excel', [ScreeningApprovalController::class, 'approveExcel'])->name('customers.finalReport.excel');

                // ? MATERIAL
                Route::get('/material/index', [ViewMaterialController::class, 'index'])->name('viewMaterial.index');
                Route::get('/material/show/{screeningId}', [ViewMaterialController::class, 'show'])->name('viewMaterial.show');
                Route::post('/material/save', [ViewMaterialController::class, 'store'])->name('material.save');
                Route::put('/material/update/{screeningId}', [ViewMaterialController::class, 'update'])->name('material.update');
                Route::get('/material/datatable', [ViewMaterialController::class, 'datatable'])->name('material.datatable');
                Route::get('/material/export', [ViewMaterialController::class, 'export'])->name('material.export');

                // ? PRODUCTIVITY REPORT
                Route::get('/productivity_report/view/{screeningId}', [ViewProductivityController::class, 'index'])->name('productivityReport.view');
                Route::get('/productivity_report/show/{screeningReportId}', [ProductivityReportController::class, 'show'])->name('productivityReport.show');
                Route::post('/productivity_report/find_components_by_family', [ComponentsDefectsAndSolutionsController::class, 'findComponentsByFamily'])->name('productivityReport.findComponentsByFamily');
                Route::post('/productivity_report/find_defects', [ComponentsDefectsAndSolutionsController::class, 'findDefects'])->name('productivityReport.findDefects');
                Route::put('/productivity-report/{screeningReportId}/defects-solutions', [ScreeningDefectsSolutionController::class, 'update'])->name('productivityReport.updateDefectsSolutions');
                Route::post('/productivity_report/finalize_screening', [ViewProductivityController::class, 'updateScreeningStatus'])->name('productivityReport.finalizeScreening');
                Route::get('/productivity_report/datatable/{screeningId}', [ProductivityReportController::class, 'datatable'])->name('productivityReport.datatable');
                Route::get('/productivity_report/export/{screeningId}', [ProductivityReportController::class, 'export'])->name('productivityReport.export');

                // ? INCLUDE
                Route::delete('/include/entry/{entryId}', [IncludeController::class, 'destroyFromInclude'])->name('IncludeEntry.destroy');
                Route::get('/include', [IncludeController::class, 'index'])->name('include.index');
                Route::get('/include/datatable/{screening_id}', [IncludeController::class, 'datatable'])->name('include.datatable');
                Route::get('/include/screenings/datatable', [IncludeController::class, 'screeningsDatatable'])->name('include.screeningsDatatable');
                Route::get('/include/{screening_id}', [IncludeController::class, 'show'])->name('include.show');
                Route::post('/include/store', [IncludeController::class, 'store'])->name('include.store');
                Route::delete('/include/all/{screeningId}', [IncludeController::class, 'destroyAllFromInclude'])->name('IncludeEntry.destroyAll');
                Route::get('/include/screenings/export', [IncludeController::class, 'export'])->name('include.screeningsExport');
                Route::get('/include/export/{screening_id}', [IncludeController::class, 'includeExport'])->name('include.includeExport');

                // ? TECH LOG
                Route::get('/techLog', [TechLogController::class, 'index'])->name('techLog.index');
                Route::get('/techLog/datatable', [TechLogController::class, 'datatable'])->name('techLog.datatable');

                // ? CALENDAR
                Route::get('/calendar/index', [CalendarController::class, 'index'])->name('calendar.index');
                Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

                // ? POSITIONING
                Route::get('/positioning/index', [PositioningController::class, 'index'])->name('positioning.index');
                Route::get('/positioning/datatable', [PositioningController::class, 'datatable'])->name('positioning.datatable');
                Route::get('/positioning/export', [PositioningController::class, 'positioningExport'])->name('positioning.export');
            });
        });
    });
