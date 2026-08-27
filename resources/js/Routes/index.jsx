import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../Hooks/useAuth';

// Layouts
import MainLayout from '../Layouts/MainLayout';
import AuthLayout from '../Layouts/AuthLayout';
import GuestLayout from '../Layouts/GuestLayout';

// Auth Pages
import Login from '../Pages/Auth/Login';
import Register from '../Pages/Auth/Register';
import ForgotPassword from '../Pages/Auth/ForgotPassword';

// Main Pages
import Dashboard from '../Pages/Dashboard/Index';
import InvoiceIndex from '../Pages/Invoices/Index';
import InvoiceCreate from '../Pages/Invoices/Create';
import InvoiceShow from '../Pages/Invoices/Show';
import InvoiceEdit from '../Pages/Invoices/Edit';
import PaymentIndex from '../Pages/Payments/Index';
import SubscriptionIndex from '../Pages/Subscriptions/Index';
import SubscriptionCreate from '../Pages/Subscriptions/Create';
import CustomerIndex from '../Pages/Customers/Index';
import CustomerCreate from '../Pages/Customers/Create';
import Settings from '../Pages/Settings/Index';
import InsightsPage from '../Pages/Insights/Index';  // <-- මෙක add කරන්න

const PrivateRoute = ({ children }) => {
  const { user, loading } = useAuth();
  
  if (loading) return <div className="flex justify-center items-center h-screen">Loading...</div>;
  
  return user ? children : <Navigate to="/login" />;
};

const AppRoutes = () => {
  return (
    <Routes>
      {/* Guest Routes */}
      <Route element={<GuestLayout />}>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
      </Route>

      {/* Protected Routes */}
      <Route element={<PrivateRoute><MainLayout /></PrivateRoute>}>
        <Route path="/" element={<Navigate to="/dashboard" />} />
        <Route path="/dashboard" element={<Dashboard />} />
        
        {/* Invoices */}
        <Route path="/invoices" element={<InvoiceIndex />} />
        <Route path="/invoices/create" element={<InvoiceCreate />} />
        <Route path="/invoices/:id" element={<InvoiceShow />} />
        <Route path="/invoices/:id/edit" element={<InvoiceEdit />} />
        
        {/* Payments */}
        <Route path="/payments" element={<PaymentIndex />} />
        
        {/* Subscriptions */}
        <Route path="/subscriptions" element={<SubscriptionIndex />} />
        <Route path="/subscriptions/create" element={<SubscriptionCreate />} />
        
        {/* Customers */}
        <Route path="/customers" element={<CustomerIndex />} />
        <Route path="/customers/create" element={<CustomerCreate />} />
        
        {/* AI Insights - මෙක add කරන්න */}
        <Route path="/insights" element={<InsightsPage />} />
        
        {/* Settings */}
        <Route path="/settings" element={<Settings />} />
      </Route>
    </Routes>
  );
};

export default AppRoutes;