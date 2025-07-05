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
        return view('donations-list', compact('donations'));
    }

    public function refund(Donation $donation)
    {
        if ($donation->status !== 'refunded') {
            $success = $this->processRefund($donation);
            
            if ($success) {
                $donation->update(['status' => 'refunded']);
                return back()->with('success', 'Refund processed successfully');
            }
        }

        return back()->with('error', 'Refund failed or already processed');
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

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($data['amount']);
        $transactionRequestType->setPayment($paymentType);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);

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

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber('4242424242424242'); // Dummy card for refund
        $creditCard->setExpirationDate('1225');

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("refundTransaction");
        $transactionRequestType->setAmount($donation->amount);
        $transactionRequestType->setPayment($paymentType);
        $transactionRequestType->setRefTransId($donation->transaction_id);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(
            env('AUTHORIZE_ENVIRONMENT') == 'sandbox' ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
        );

        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse != null && $tresponse->getMessages() != null) {
                return true;
            }
        }

        return false;
    }
}
