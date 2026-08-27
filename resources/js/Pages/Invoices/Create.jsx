import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import { AIPromptInput } from '../../Components/AI/AIPromptInput';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const InvoiceCreate = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [customers, setCustomers] = useState([]);
  const [formData, setFormData] = useState({
    customer_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    tax_rate: 15,
    currency: 'LKR',
    items: [{ description: '', quantity: 1, unit_price: 0 }],
  });

  useEffect(() => {
    fetchCustomers();
  }, []);

  const fetchCustomers = async () => {
    try {
      const response = await api.get('/customers');
      setCustomers(response.data.data);
    } catch (error) {
      toast.error('Failed to fetch customers');
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
    setLoading(true);

    try {
      await api.post('/invoices', formData);
      toast.success('Invoice created successfully!');
      navigate('/invoices');
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to create invoice');
    } finally {
      setLoading(false);
    }
  };

  const handleAIGenerate = async (prompt) => {
    setLoading(true);
    try {
      const response = await api.post('/ai/generate-invoice', { prompt });
      const data = response.data.parsed_data;
      
      setFormData({
        ...formData,
        customer_id: response.data.invoice.customer_id,
        due_date: data.due_date || formData.due_date,
        items: data.items.map((item) => ({
          description: item.description,
          quantity: item.quantity || 1,
          unit_price: item.unit_price || 0,
        })),
      });
      
      toast.success('AI generated invoice draft!');
    } catch (error) {
      toast.error(error.response?.data?.error || 'AI generation failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Create Invoice</h1>
        <Button variant="outline" onClick={() => navigate('/invoices')}>
          Cancel
        </Button>
      </div>

      <div className="space-y-6">
        <AIPromptInput onGenerate={handleAIGenerate} loading={loading} />

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
                onChange={(e) => handleChange('tax_rate', parseFloat(e.target.value))}
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

            <div className="flex justify-end gap-2 pt-4 border-t">
              <Button type="button" variant="outline" onClick={() => navigate('/invoices')}>
                Cancel
              </Button>
              <Button type="submit" disabled={loading}>
                {loading ? 'Creating...' : 'Create Invoice'}
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </div>
  );
};

export default InvoiceCreate;