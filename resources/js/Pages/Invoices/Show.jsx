import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Badge } from '../../Components/Common/Badge';
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

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">
          Invoice #{invoice.invoice_number}
        </h1>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => navigate('/invoices')}>
            Back
          </Button>
          {invoice.can_be_edited && (
            <Button variant="outline" onClick={() => navigate(`/invoices/${id}/edit`)}>
              Edit
            </Button>
          )}
          {invoice.status === 'draft' && (
            <Button onClick={handleSend} disabled={sending}>
              {sending ? 'Sending...' : 'Send Invoice'}
            </Button>
          )}
          {invoice.status === 'sent' || invoice.status === 'partially_paid' && (
            <Button variant="success" onClick={handleMarkPaid}>
              Mark as Paid
            </Button>
          )}
          <Button variant="outline" onClick={handleDuplicate}>
            Duplicate
          </Button>
        </div>
      </div>

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
          {invoice.customer?.phone && (
            <p className="text-sm text-gray-600">{invoice.customer?.phone}</p>
          )}
          {invoice.customer?.address && (
            <p className="text-sm text-gray-600 whitespace-pre-line">
              {invoice.customer?.address}
            </p>
          )}
        </Card>
      </div>

      {/* Items */}
      <Card title="Invoice Items" className="mt-6">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-2 text-left text-sm font-medium text-gray-500">Description</th>
                <th className="px-4 py-2 text-right text-sm font-medium text-gray-500">Qty</th>
                <th className="px-4 py-2 text-right text-sm font-medium text-gray-500">Price</th>
                <th className="px-4 py-2 text-right text-sm font-medium text-gray-500">Total</th>
              </tr>
            </thead>
            <tbody>
              {invoice.items?.map((item, index) => (
                <tr key={index} className="border-t border-gray-100">
                  <td className="px-4 py-3 text-sm">{item.description}</td>
                  <td className="px-4 py-3 text-sm text-right">{item.quantity}</td>
                  <td className="px-4 py-3 text-sm text-right">
                    {formatCurrency(item.unit_price, invoice.currency)}
                  </td>
                  <td className="px-4 py-3 text-sm text-right font-semibold">
                    {formatCurrency(item.total, invoice.currency)}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="border-t-2 border-gray-200">
              <tr>
                <td colSpan="3" className="px-4 py-3 text-right font-medium">Subtotal</td>
                <td className="px-4 py-3 text-right">{formatCurrency(invoice.subtotal, invoice.currency)}</td>
              </tr>
              <tr>
                <td colSpan="3" className="px-4 py-3 text-right font-medium">Tax ({invoice.tax_rate}%)</td>
                <td className="px-4 py-3 text-right">{formatCurrency(invoice.tax, invoice.currency)}</td>
              </tr>
              <tr>
                <td colSpan="3" className="px-4 py-3 text-right font-bold text-lg">Total</td>
                <td className="px-4 py-3 text-right font-bold text-lg">
                  {formatCurrency(invoice.total, invoice.currency)}
                </td>
              </tr>
              {invoice.balance_due > 0 && invoice.status !== 'paid' && (
                <tr>
                  <td colSpan="3" className="px-4 py-3 text-right font-semibold text-red-600">Balance Due</td>
                  <td className="px-4 py-3 text-right font-semibold text-red-600">
                    {formatCurrency(invoice.balance_due, invoice.currency)}
                  </td>
                </tr>
              )}
            </tfoot>
          </table>
        </div>
      </Card>

      {/* Notes */}
      {invoice.notes && (
        <Card title="Notes" className="mt-6">
          <p className="text-gray-600 whitespace-pre-line">{invoice.notes}</p>
        </Card>
      )}
    </div>
  );
};

export default InvoiceShow;