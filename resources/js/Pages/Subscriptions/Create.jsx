import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const SubscriptionCreate = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [customers, setCustomers] = useState([]);
  const [formData, setFormData] = useState({
    customer_id: '',
    name: '',
    description: '',
    amount: '',
    currency: 'LKR',
    billing_cycle: 'monthly',
    billing_period: 1,
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    trial_ends_at: '',
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

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post('/subscriptions', formData);
      toast.success('Subscription created successfully!');
      navigate('/subscriptions');
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to create subscription');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Create Subscription</h1>
        <Button variant="outline" onClick={() => navigate('/subscriptions')}>
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
            <Input
              label="Subscription Name"
              placeholder="Monthly Service"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              required
            />
          </div>

          <Input
            label="Description"
            placeholder="Description of the subscription"
            value={formData.description}
            onChange={(e) => handleChange('description', e.target.value)}
          />

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="Amount"
              type="number"
              placeholder="25000"
              value={formData.amount}
              onChange={(e) => handleChange('amount', e.target.value)}
              required
            />
            <Input
              label="Currency"
              value={formData.currency}
              onChange={(e) => handleChange('currency', e.target.value)}
              placeholder="LKR"
            />
            <Select
              label="Billing Cycle"
              options={[
                { value: 'daily', label: 'Daily' },
                { value: 'weekly', label: 'Weekly' },
                { value: 'monthly', label: 'Monthly' },
                { value: 'quarterly', label: 'Quarterly' },
                { value: 'yearly', label: 'Yearly' },
              ]}
              value={formData.billing_cycle}
              onChange={(e) => handleChange('billing_cycle', e.target.value)}
              required
            />
          </div>

          <Input
            label="Billing Period"
            type="number"
            placeholder="1"
            value={formData.billing_period}
            onChange={(e) => handleChange('billing_period', parseInt(e.target.value) || 1)}
          />

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="Start Date"
              type="date"
              value={formData.start_date}
              onChange={(e) => handleChange('start_date', e.target.value)}
              required
            />
            <Input
              label="End Date (Optional)"
              type="date"
              value={formData.end_date}
              onChange={(e) => handleChange('end_date', e.target.value)}
            />
            <Input
              label="Trial Ends At (Optional)"
              type="date"
              value={formData.trial_ends_at}
              onChange={(e) => handleChange('trial_ends_at', e.target.value)}
            />
          </div>

          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button type="button" variant="outline" onClick={() => navigate('/subscriptions')}>
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Creating...' : 'Create Subscription'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default SubscriptionCreate;