<?php

namespace Vendor\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Models\Payment;
use Vendor\Invoice\Http\Requests\InvoiceRequest;
use Vendor\Invoice\Http\Requests\QuoteRequest;
use Vendor\Invoice\Http\Requests\PaymentRequest;
use Vendor\Invoice\Services\InvoiceService;
use Vendor\Invoice\Exports\InvoicesExport;
use Vendor\Invoice\Imports\InvoicesImport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service) {}

    /* ================================================================
       INVOICES — CRUD
    ================================================================ */

    public function index()
    {
        return view('invoice::invoices.index', [
            'statuses'       => config('invoice.invoice_statuses'),
            'currencies'     => config('invoice.currencies'),
            'payment_methods'=> config('invoice.payment_methods'),
        ]);
    }

    public function create()
    {
        return view('invoice::invoices.create', [
            'currencies'          => config('invoice.currencies'),
            'payment_terms'       => config('invoice.payment_terms'),
            'payment_methods'     => config('invoice.payment_methods'),
            'tax_rates'           => config('invoice.tax.rates'),
            'withholding_rates'   => config('invoice.withholding_tax.rates'),
            'discount_types'      => config('invoice.discount.types'),
        ]);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        try {
            $data              = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;

            $invoice = $this->service->createInvoice($data);

            return response()->json([
                'success'  => true,
                'message'  => 'Facture créée avec succès.',
                'data'     => $invoice,
                'redirect' => route('invoices.show', $invoice),
            ], 201);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client','items','payments','user']);
        return view('invoice::invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Impossible de modifier une facture payée.');
        $invoice->load(['client','items']);
        return view('invoice::invoices.edit', [
            'invoice'           => $invoice,
            'currencies'        => config('invoice.currencies'),
            'payment_terms'     => config('invoice.payment_terms'),
            'payment_methods'   => config('invoice.payment_methods'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $invoice = $this->service->updateInvoice($invoice, $request->validated());
            return response()->json([
                'success'  => true,
                'message'  => 'Facture mise à jour.',
                'data'     => $invoice,
                'redirect' => route('invoices.show', $invoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            $this->service->deleteInvoice($invoice);
            return response()->json(['success' => true, 'message' => 'Facture supprimée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ================================================================
       AJAX DATA / STATS
    ================================================================ */

    public function getData(Request $request): JsonResponse
    {
        $invoices = $this->service->getFilteredInvoices($request->all());
        return response()->json([
            'data'         => $invoices->items(),
            'current_page' => $invoices->currentPage(),
            'last_page'    => $invoices->lastPage(),
            'per_page'     => $invoices->perPage(),
            'total'        => $invoices->total(),
            'from'         => $invoices->firstItem(),
            'to'           => $invoices->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getStats()]);
    }

    /* ================================================================
       ACTIONS MÉTIER
    ================================================================ */

    public function send(Invoice $invoice): JsonResponse
    {
        try {
            $invoice->markAsSent();
            // TODO: dispatch SendInvoiceEmail job
            return response()->json(['success' => true, 'message' => 'Facture marquée comme envoyée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function duplicate(Invoice $invoice): JsonResponse
    {
        try {
            $data = $invoice->only([
                'client_id','currency','exchange_rate','payment_terms',
                'discount_type','discount_value','tax_rate','withholding_tax_rate',
                'notes','terms','footer',
            ]);
            $data['issue_date'] = now()->toDateString();
            $data['due_date']   = now()->addDays($invoice->payment_terms)->toDateString();
            $data['items']      = $invoice->items->toArray();
            $data['tenant_id']  = $invoice->tenant_id;

            $newInvoice = $this->service->createInvoice($data);
            return response()->json([
                'success'  => true,
                'message'  => 'Facture dupliquée.',
                'redirect' => route('invoices.edit', $newInvoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ================================================================
       PAIEMENTS
    ================================================================ */

    public function addPayment(PaymentRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $payment = $this->service->addPayment($invoice, $request->validated());
            return response()->json(['success' => true, 'message' => 'Paiement enregistré.', 'data' => $payment], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deletePayment(Payment $payment): JsonResponse
    {
        try {
            $this->service->deletePayment($payment);
            return response()->json(['success' => true, 'message' => 'Paiement supprimé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ================================================================
       EXPORTS / IMPORT
    ================================================================ */

    public function exportCsv()
    {
        return Excel::download(new InvoicesExport, 'factures_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new InvoicesExport, 'factures_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $invoices = Invoice::with('client')->filter([])->get();
        $pdf = app('dompdf.wrapper')->loadView('invoice::exports.pdf', compact('invoices'));
        return $pdf->download('factures_' . date('Y-m-d') . '.pdf');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['client','items','payments','tenant']);
        $pdf = app('dompdf.wrapper')->loadView('invoice::pdf.invoice', compact('invoice'));
        return $pdf->download("facture-{$invoice->number}.pdf");
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new InvoicesImport, $request->file('file'));
            return response()->json(['success' => true, 'message' => 'Factures importées avec succès.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => "Erreur d'importation : " . $e->getMessage()], 500);
        }
    }

    /* ================================================================
       QUOTES — CRUD
    ================================================================ */

    public function quotesIndex()
    {
        return view('invoice::quotes.index', [
            'statuses'   => config('invoice.quote_statuses'),
            'currencies' => config('invoice.currencies'),
        ]);
    }

    public function quotesCreate()
    {
        return view('invoice::quotes.create', [
            'currencies'        => config('invoice.currencies'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function quotesStore(QuoteRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;
            $quote = $this->service->createQuote($data);
            return response()->json([
                'success'  => true,
                'message'  => 'Devis créé avec succès.',
                'data'     => $quote,
                'redirect' => route('invoices.quotes.show', $quote),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quotesShow(Quote $quote)
    {
        $quote->load(['client','items','user','invoice']);
        return view('invoice::quotes.show', compact('quote'));
    }

    public function quotesEdit(Quote $quote)
    {
        abort_if(in_array($quote->status, ['accepted','declined']), 403, 'Ce devis ne peut plus être modifié.');
        $quote->load(['client','items']);
        return view('invoice::quotes.edit', [
            'quote'             => $quote,
            'currencies'        => config('invoice.currencies'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function quotesUpdate(QuoteRequest $request, Quote $quote): JsonResponse
    {
        try {
            $quote = $this->service->updateQuote($quote, $request->validated());
            return response()->json([
                'success'  => true,
                'message'  => 'Devis mis à jour.',
                'data'     => $quote,
                'redirect' => route('invoices.quotes.show', $quote),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quotesDestroy(Quote $quote): JsonResponse
    {
        try {
            $this->service->deleteQuote($quote);
            return response()->json(['success' => true, 'message' => 'Devis supprimé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function quotesConvert(Quote $quote): JsonResponse
    {
        try {
            $invoice = $this->service->convertQuoteToInvoice($quote);
            return response()->json([
                'success'  => true,
                'message'  => 'Devis converti en facture avec succès.',
                'redirect' => route('invoices.show', $invoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function quotesGetData(Request $request): JsonResponse
    {
        $quotes = $this->service->getFilteredQuotes($request->all());
        return response()->json([
            'data'         => $quotes->items(),
            'current_page' => $quotes->currentPage(),
            'last_page'    => $quotes->lastPage(),
            'per_page'     => $quotes->perPage(),
            'total'        => $quotes->total(),
        ]);
    }

    public function quotesDownloadPdf(Quote $quote)
    {
        $quote->load(['client','items','tenant']);
        $pdf = app('dompdf.wrapper')->loadView('invoice::pdf.quote', compact('quote'));
        return $pdf->download("devis-{$quote->number}.pdf");
    }

    /* ================================================================
       DEVISE — AJAX
    ================================================================ */

    public function getExchangeRate(Request $request): JsonResponse
    {
        $from = strtoupper($request->string('from', 'EUR'));
        $to   = strtoupper($request->string('to', 'EUR'));

        $fromDef = config("invoice.currencies.{$from}");
        $toDef   = config("invoice.currencies.{$to}");

        if (!$fromDef || !$toDef) {
            return response()->json(['success' => false, 'message' => 'Devise inconnue.'], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'from'   => $from,
                'to'     => $to,
                'rate'   => 1.0, // Intégrer une API de taux (OpenExchangeRates, Fixer.io…)
                'symbol' => $toDef['symbol'],
            ],
        ]);
    }
}
