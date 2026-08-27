import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../Hooks/useAuth';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import toast from 'react-hot-toast';

const Register = () => {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    company_name: '',
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    // Validation
    if (formData.password !== formData.password_confirmation) {
      toast.error('Passwords do not match');
      setLoading(false);
      return;
    }

    const result = await register(formData);
    setLoading(false);
    
    if (result.success) {
      toast.success('Registration successful!');
      navigate('/dashboard');
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <Input
        label="Full Name"
        type="text"
        placeholder="John Doe"
        value={formData.name}
        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
        required
      />
      <Input
        label="Email Address"
        type="email"
        placeholder="you@example.com"
        value={formData.email}
        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
        required
      />
      <Input
        label="Company Name"
        type="text"
        placeholder="Your Company Name"
        value={formData.company_name}
        onChange={(e) => setFormData({ ...formData, company_name: e.target.value })}
        required
      />
      <Input
        label="Password"
        type="password"
        placeholder="••••••••"
        value={formData.password}
        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
        required
      />
      <Input
        label="Confirm Password"
        type="password"
        placeholder="••••••••"
        value={formData.password_confirmation}
        onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
        required
      />
      <Button type="submit" className="w-full" disabled={loading}>
        {loading ? 'Registering...' : 'Register'}
      </Button>
      <p className="text-center text-sm text-gray-600">
        Already have an account?{' '}
        <Link to="/login" className="text-blue-600 hover:underline">
          Login
        </Link>
      </p>
    </form>
  );
};

export default Register;