import React, { useState, useEffect } from 'react';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const Settings = () => {
  const [loading, setLoading] = useState(false);
  const [profile, setProfile] = useState({
    name: '',
    email: '',
    company: {
      name: '',
      email: '',
      phone: '',
      address: '',
      tax_rate: 15,
      currency: 'LKR',
    },
    settings: {
      invoice_prefix: 'INV',
      default_due_days: 30,
      default_tax_rate: 15,
      default_currency: 'LKR',
    },
  });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const [userRes, companyRes] = await Promise.all([
        api.get('/auth/user'),
        api.get('/company'),
      ]);
      
      const user = userRes.data.user;
      const company = companyRes.data.company;
      
      setProfile({
        name: user.name,
        email: user.email,
        company: {
          name: company.name,
          email: company.email || '',
          phone: company.phone || '',
          address: company.address || '',
          tax_rate: company.tax_rate || 15,
          currency: company.currency || 'LKR',
        },
        settings: {
          invoice_prefix: company.settings?.invoice_prefix || 'INV',
          default_due_days: company.settings?.default_due_days || 30,
          default_tax_rate: company.settings?.default_tax_rate || 15,
          default_currency: company.settings?.default_currency || 'LKR',
        },
      });
    } catch (error) {
      toast.error('Failed to load settings');
    }
  };

  const handleProfileChange = (key, value) => {
    setProfile((prev) => ({ ...prev, [key]: value }));
  };

  const handleCompanyChange = (key, value) => {
    setProfile((prev) => ({
      ...prev,
      company: { ...prev.company, [key]: value },
    }));
  };

  const handleSettingsChange = (key, value) => {
    setProfile((prev) => ({
      ...prev,
      settings: { ...prev.settings, [key]: value },
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // Update company
      await api.put('/company', profile.company);
      
      // Update settings
      await api.put('/company/settings', { settings: profile.settings });
      
      toast.success('Settings updated successfully!');
    } catch (error) {
      toast.error('Failed to update settings');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Settings</h1>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Profile Settings */}
        <Card title="Profile Settings">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Full Name"
              value={profile.name}
              onChange={(e) => handleProfileChange('name', e.target.value)}
              required
            />
            <Input
              label="Email Address"
              type="email"
              value={profile.email}
              onChange={(e) => handleProfileChange('email', e.target.value)}
              required
            />
          </div>
        </Card>

        {/* Company Settings */}
        <Card title="Company Information">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Company Name"
              value={profile.company.name}
              onChange={(e) => handleCompanyChange('name', e.target.value)}
              required
            />
            <Input
              label="Company Email"
              type="email"
              value={profile.company.email}
              onChange={(e) => handleCompanyChange('email', e.target.value)}
            />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <Input
              label="Phone Number"
              value={profile.company.phone}
              onChange={(e) => handleCompanyChange('phone', e.target.value)}
            />
            <Input
              label="Address"
              value={profile.company.address}
              onChange={(e) => handleCompanyChange('address', e.target.value)}
            />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <Input
              label="Default Tax Rate (%)"
              type="number"
              value={profile.company.tax_rate}
              onChange={(e) => handleCompanyChange('tax_rate', parseFloat(e.target.value) || 0)}
            />
            <Input
              label="Default Currency"
              value={profile.company.currency}
              onChange={(e) => handleCompanyChange('currency', e.target.value)}
              placeholder="LKR"
            />
          </div>
        </Card>

        {/* Invoice Settings */}
        <Card title="Invoice Settings">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Invoice Prefix"
              value={profile.settings.invoice_prefix}
              onChange={(e) => handleSettingsChange('invoice_prefix', e.target.value)}
              placeholder="INV"
            />
            <Input
              label="Default Due Days"
              type="number"
              value={profile.settings.default_due_days}
              onChange={(e) => handleSettingsChange('default_due_days', parseInt(e.target.value) || 30)}
            />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <Input
              label="Default Tax Rate (%)"
              type="number"
              value={profile.settings.default_tax_rate}
              onChange={(e) => handleSettingsChange('default_tax_rate', parseFloat(e.target.value) || 0)}
            />
            <Input
              label="Default Currency"
              value={profile.settings.default_currency}
              onChange={(e) => handleSettingsChange('default_currency', e.target.value)}
              placeholder="LKR"
            />
          </div>
        </Card>

        <div className="flex justify-end">
          <Button type="submit" disabled={loading}>
            {loading ? 'Saving...' : 'Save Settings'}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default Settings;