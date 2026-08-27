import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import { Spinner } from '../../Components/Common/Spinner';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const InvoiceEdit = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [customers, setCustomers] = useState([]);
  const [formData, setFormData] = useState({
    customer_id: '',
    issue_date: '',
    due_date: '',
    tax_rate: 15,
    currency: 'LKR',
    notes: '',
    terms: '',
    items: [],
  });

  useEffect(() => {
    fetchData();
  }, [id]);

  const fetchData = async () => {
    try {
      const [invoiceRes, customersRes] = await Promise.all([
        api.get(`/invoices/${id}`),
        api.get('/customers'),
      ]);
      
      const invoice = invoiceRes.data.data;
      setFormData({
        customer_id: invoice.customer_id,
        issue_date: invoice.issue_date,
        due_date: invoice.due_date,
        tax_rate: invoice.tax_rate,
        currency: invoice.currency,
        notes: invoice.notes || '',
        terms: invoice.terms || '',
        items: invoice.items || [{ description: '', quantity: 1, unit_price: 0 }],
      });
      setCustomers(customersRes.data.data);
    } catch (error) {
      toast.error('Failed to load invoice');
      navigate('/invoices');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (key, value) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const handleItemChange = (index, key, value) => {
    const newItems = [...formData.items];
    newItems[index][key] = value;
    setFormData((prev) => ({ ...prev, items: newItems }));
  };

  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { description: '', quantity: 1, unit_price: 0 }],
    }));
  };

  const removeItem = (index) => {
    if (formData.items.length === 1) {
      toast.error('At least one item is required');
      return;
    }
    const newItems = formData.items.filter((_, i) => i !== index);
    setFormData((prev) => ({ ...prev, items: newItems }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      await api.put(`/invoices/${id}`, formData);
      toast.success('Invoice updated successfully!');
      navigate(`/invoices/${id}`);
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to update invoice');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Edit Invoice #{id}</h1>
        <Button variant="outline" onClick={() => navigate(`/invoices/${id}`)}>
          Cancel
        </Button>
      </div>

      <Card>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Select
              label="Customer"
              options={customers.map((c) => ({
                value: c.id,
                label: c.name + ' (' + c.email + ')',
              }))}
              value={formData.customer_id}
              onChange={(e) => handleChange('customer_id', e.target.value)}
              required
              placeholder="Select customer"
            />
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="Issue Date"
                type="date"
                value={formData.issue_date}
                onChange={(e) => handleChange('issue_date', e.target.value)}
                required
              />
              <Input
                label="Due Date"
                type="date"
                value={formData.due_date}
                onChange={(e) => handleChange('due_date', e.target.value)}
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Tax Rate (%)"
              type="number"
              value={formData.tax_rate}
              onChange={(e) => handleChange('tax_rate', parseFloat(e.target.value) || 0)}
            />
            <Input
              label="Currency"
              value={formData.currency}
              onChange={(e) => handleChange('currency', e.target.value)}
              placeholder="LKR"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Items
            </label>
            {formData.items.map((item, index) => (
              <div key={index} className="flex gap-2 mb-2">
                <Input
                  placeholder="Description"
                  value={item.description}
                  onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                  className="flex-1"
                  required
                />
                <Input
                  type="number"
                  placeholder="Qty"
                  value={item.quantity}
                  onChange={(e) => handleItemChange(index, 'quantity', parseFloat(e.target.value) || 0)}
                  className="w-20"
                  required
                />
                <Input
                  type="number"
                  placeholder="Price"
                  value={item.unit_price}
                  onChange={(e) => handleItemChange(index, 'unit_price', parseFloat(e.target.value) || 0)}
                  className="w-28"
                  required
                />
                <Button
                  type="button"
                  variant="danger"
                  size="sm"
                  onClick={() => removeItem(index)}
                >
                  ×
                </Button>
              </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={addItem}>
              + Add Item
            </Button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Notes"
              type="textarea"
              value={formData.notes}
              onChange={(e) => handleChange('notes', e.target.value)}
              rows="3"
            />
            <Input
              label="Terms"
              type="textarea"
              value={formData.terms}
              onChange={(e) => handleChange('terms', e.target.value)}
              rows="3"
            />
          </div>

          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button type="button" variant="outline" onClick={() => navigate(`/invoices/${id}`)}>
              Cancel
            </Button>
            <Button type="submit" disabled={submitting}>
              {submitting ? 'Saving...' : 'Update Invoice'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default InvoiceEdit;