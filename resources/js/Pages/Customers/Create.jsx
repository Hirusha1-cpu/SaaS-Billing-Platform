import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const CustomerCreate = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: 'Sri Lanka',
    tax_id: '',
    company_name: '',
    website: '',
    notes: '',
    is_active: true,
  });

  const handleChange = (key, value) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post('/customers', formData);
      toast.success('Customer created successfully!');
      navigate('/customers');
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to create customer');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Create Customer</h1>
        <Button variant="outline" onClick={() => navigate('/customers')}>
          Cancel
        </Button>
      </div>

      <Card>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Full Name"
              placeholder="John Doe"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              required
            />
            <Input
              label="Email Address"
              type="email"
              placeholder="john@example.com"
              value={formData.email}
              onChange={(e) => handleChange('email', e.target.value)}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Phone Number"
              placeholder="0712345678"
              value={formData.phone}
              onChange={(e) => handleChange('phone', e.target.value)}
            />
            <Input
              label="Company Name"
              placeholder="ABC Pvt Ltd"
              value={formData.company_name}
              onChange={(e) => handleChange('company_name', e.target.value)}
            />
          </div>

          <Input
            label="Address"
            placeholder="123 Main Street"
            value={formData.address}
            onChange={(e) => handleChange('address', e.target.value)}
          />

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="City"
              placeholder="Colombo"
              value={formData.city}
              onChange={(e) => handleChange('city', e.target.value)}
            />
            <Input
              label="State/Province"
              placeholder="Western"
              value={formData.state}
              onChange={(e) => handleChange('state', e.target.value)}
            />
            <Input
              label="ZIP/Postal Code"
              placeholder="10100"
              value={formData.zip}
              onChange={(e) => handleChange('zip', e.target.value)}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Country"
              placeholder="Sri Lanka"
              value={formData.country}
              onChange={(e) => handleChange('country', e.target.value)}
            />
            <Input
              label="Tax ID / VAT Number"
              placeholder="123456789"
              value={formData.tax_id}
              onChange={(e) => handleChange('tax_id', e.target.value)}
            />
          </div>

          <Input
            label="Website"
            placeholder="https://example.com"
            value={formData.website}
            onChange={(e) => handleChange('website', e.target.value)}
          />

          <Input
            label="Notes"
            type="textarea"
            placeholder="Additional notes about the customer..."
            value={formData.notes}
            onChange={(e) => handleChange('notes', e.target.value)}
            rows="3"
          />

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              checked={formData.is_active}
              onChange={(e) => handleChange('is_active', e.target.checked)}
              className="w-4 h-4 text-blue-600"
            />
            <label htmlFor="is_active" className="text-sm text-gray-700">
              Active Customer
            </label>
          </div>

          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button type="button" variant="outline" onClick={() => navigate('/customers')}>
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Creating...' : 'Create Customer'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default CustomerCreate;