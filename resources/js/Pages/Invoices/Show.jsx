import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Spinner } from '../../Components/Common/Spinner';
import { InvoiceStatusBadge } from '../../Components/Invoices/InvoiceStatusBadge';
import api from '../../Utils/api';
import { formatCurrency, formatDate } from '../../Utils/helpers';
import toast from 'react-hot-toast';

const InvoiceShow = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    fetchInvoice();
  }, [id]);

  const fetchInvoice = async () => {
    try {
      const response = await api.get(`/invoices/${id}`);
      setInvoice(response.data.data);
    } catch (error) {
      toast.error('Failed to load invoice');
      navigate('/invoices');
    } finally {
      setLoading(false);
    }
  };

  const handleSend = async () => {
    setSending(true);
    try {
      await api.post(`/invoices/${id}/send`);
      toast.success('Invoice sent successfully!');
      fetchInvoice();
    } catch (error) {
      toast.error('Failed to send invoice');
    } finally {
      setSending(false);
    }
  };

  const handlePay = async () => {
    setProcessing(true);
    try {
      const response = await api.post('/payments/create-stripe-session', {
        invoice_id: parseInt(id)
      });
      
      // Redirect to Stripe Checkout
      window.location.href = response.data.checkout_url;
      
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to initiate payment');
      setProcessing(false);
    }
  };

  const handleMarkPaid = async () => {
    try {
      await api.post(`/invoices/${id}/mark-paid`);
      toast.success('Invoice marked as paid!');
      fetchInvoice();
    } catch (error) {
      toast.error('Failed to mark invoice as paid');
    }
  };

  const handleDuplicate = async () => {
    try {
      const response = await api.post(`/invoices/${id}/duplicate`);
      toast.success('Invoice duplicated successfully!');
      navigate(`/invoices/${response.data.id}`);
    } catch (error) {
      toast.error('Failed to duplicate invoice');
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  if (!invoice) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Invoice not found</p>
        <Link to="/invoices" className="text-blue-600 hover:underline mt-2 inline-block">
          Back to Invoices
        </Link>
      </div>
    );
  }

  // Check if invoice can be paid
  const canPay = invoice.status === 'sent' || invoice.status === 'partially_paid';
  const isPaid = invoice.status === 'paid';

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">
          Invoice #{invoice.invoice_number}
        </h1>
        <div className="flex gap-2 flex-wrap">
          <Button variant="outline" onClick={() => navigate('/invoices')}>
            Back
          </Button>
          
          {invoice.status === 'draft' && (
            <>
              <Button variant="outline" onClick={() => navigate(`/invoices/${id}/edit`)}>
                Edit
              </Button>
              <Button onClick={handleSend} disabled={sending}>
                {sending ? 'Sending...' : 'Send Invoice'}
              </Button>
            </>
          )}

          {/* ============ PAY BUTTON ============ */}
          {canPay && (
            <Button 
              variant="success" 
              onClick={handlePay} 
              disabled={processing}
              className="bg-green-600 hover:bg-green-700"
            >
              {processing ? 'Processing...' : '💳 Pay Now'}
            </Button>
          )}

          {invoice.status === 'paid' && (
            <Button variant="success" disabled>
              ✅ Paid
            </Button>
          )}

          {invoice.status === 'sent' && (
            <Button variant="outline" onClick={handleMarkPaid}>
              Mark as Paid
            </Button>
          )}

          <Button variant="outline" onClick={handleDuplicate}>
            Duplicate
          </Button>
        </div>
      </div>

      {/* Rest of the invoice details... */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Invoice Info */}
        <Card className="lg:col-span-2">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-500">Invoice Number</p>
              <p className="font-semibold">{invoice.invoice_number}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Status</p>
              <InvoiceStatusBadge status={invoice.status} />
            </div>
            <div>
              <p className="text-sm text-gray-500">Issue Date</p>
              <p>{formatDate(invoice.issue_date)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Due Date</p>
              <p className={invoice.is_overdue ? 'text-red-600 font-semibold' : ''}>
                {formatDate(invoice.due_date)}
                {invoice.is_overdue && ' (Overdue)'}
              </p>
            </div>
          </div>
        </Card>

        {/* Customer Info */}
        <Card title="Customer">
          <p className="font-semibold">{invoice.customer?.name}</p>
          <p className="text-sm text-gray-600">{invoice.customer?.email}</p>
        </Card>
      </div>

      {/* Balance Due Highlight */}
      {canPay && invoice.balance_due > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
          <div className="flex justify-between items-center">
            <div>
              <p className="text-sm text-yellow-700">Balance Due</p>
              <p className="text-2xl font-bold text-yellow-800">
                {formatCurrency(invoice.balance_due, invoice.currency)}
              </p>
            </div>
            <Button 
              variant="success" 
              onClick={handlePay} 
              disabled={processing}
              className="bg-green-600 hover:bg-green-700 text-lg px-8 py-3"
            >
              {processing ? 'Processing...' : '💳 Pay Now'}
            </Button>
          </div>
        </div>
      )}

      {/* ... rest of invoice items, etc. */}
    </div>
  );
};

export default InvoiceShow;