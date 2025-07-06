<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class DonationController extends Controller
{
    public function index()
    {
        return view('donation-form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
            'card_number' => 'required|string',
            'exp_date' => 'required|string',
            'cvv' => 'required|string',
        ]);

        // Process payment
        $transactionId = $this->processPayment($validated);

        if ($transactionId) {
            // Save donation
            Donation::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'amount' => $validated['amount'],
                'transaction_id' => $transactionId,
            ]);

            return redirect()->route('donations.form')->with('success', 'Thank you for your donation!');
        }

        return back()->with('error', 'Payment failed. Please try again.');
    }

    public function list()
    {
        $donations = Donation::all();
        //dd($donations);
        return view('donations-list', compact('donations'));
    }

public function refund(Donation $donation)
{
    // Check if already refunded
    if ($donation->status === 'refunded') {
        return back()->with('error', 'This donation was already refunded');
    }

    // Process refund
    $result = $this->processRefund($donation);

    if ($result['success']) {
        $donation->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_transaction_id' => $result['transaction_id']
        ]);
        
        return back()->with('success', 'Refund processed successfully. Transaction ID: ' . $result['transaction_id']);
    }

    return back()->with('error', 'Refund failed: ' . $result['message']);
}

    private function processPayment(array $data)
    {
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_API_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber(str_replace(' ', '', $data['card_number']));
        $creditCard->setExpirationDate($data['exp_date']);
        $creditCard->setCardCode($data['cvv']);
       // dd($creditCard);
        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);
        
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($data['amount']);
        $transactionRequestType->setPayment($paymentType);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);
        //dd($request);
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(
            env('AUTHORIZE_ENVIRONMENT') == 'sandbox' ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
        );

        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse != null && $tresponse->getMessages() != null) {
                return $tresponse->getTransId();
            }
        }

        return null;
    }

private function processRefund(Donation $donation)
{
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName(env('AUTHORIZE_API_LOGIN_ID'));
    $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

    // Create the payment data for refund
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber('XXXX' . substr($donation->transaction_id, -4)); // Last 4 digits for reference
    $creditCard->setExpirationDate('XXXX'); // Not needed for refund but required by API

    $paymentType = new AnetAPI\PaymentType();
    $paymentType->setCreditCard($creditCard);

    // Create refund transaction
    $transactionRequest = new AnetAPI\TransactionRequestType();
    $transactionRequest->setTransactionType("refundTransaction");
    $transactionRequest->setAmount($donation->amount);
    $transactionRequest->setPayment($paymentType);
    $transactionRequest->setRefTransId($donation->transaction_id);

    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setTransactionRequest($transactionRequest);

    $controller = new AnetController\CreateTransactionController($request);
    
    try {
        $response = $controller->executeWithApiResponse(
            env('AUTHORIZE_ENVIRONMENT') === 'sandbox' ? 
            \net\authorize\api\constants\ANetEnvironment::SANDBOX : 
            \net\authorize\api\constants\ANetEnvironment::PRODUCTION
        );

        if ($response != null) {
            $tresponse = $response->getTransactionResponse();
            
            if ($response->getMessages()->getResultCode() == "Ok" && $tresponse != null) {
                return [
                    'success' => true,
                    'transaction_id' => $tresponse->getTransId(),
                    'message' => 'Refund successful'
                ];
            } else {
                $errorMessages = [];
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    foreach ($tresponse->getErrors() as $error) {
                        $errorMessages[] = $error->getErrorText();
                    }
                } else {
                    foreach ($response->getMessages()->getMessage() as $message) {
                        $errorMessages[] = $message->getText();
                    }
                }
                return [
                    'success' => false,
                    'message' => implode(', ', $errorMessages)
                ];
            }
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Refund processing error: ' . $e->getMessage()
        ];
    }

    return [
        'success' => false,
        'message' => 'No response from payment processor'
    ];
}
}