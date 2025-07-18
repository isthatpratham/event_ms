import React from 'react';
import { Card } from '@/components/ui/card';
import { ChevronRight, Building, Warehouse, Construction, ClipboardCheck, Phone, Mail, MapPin } from 'lucide-react';

const ConstructionWebsite = () => {
  return (
    <div className="min-h-screen bg-white">
      {/* Header/Navigation */}
      <header className="fixed w-full top-0 z-50 bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4">
          <nav className="flex items-center justify-between h-20">
            <div className="text-2xl font-bold text-gray-900">BR CONSTRUCTION</div>
            <div className="hidden md:flex items-center space-x-8">
              <a href="#home" className="text-gray-600 hover:text-blue-600 font-medium">Home</a>
              <a href="#services" className="text-gray-600 hover:text-blue-600 font-medium">Services</a>
              <a href="#projects" className="text-gray-600 hover:text-blue-600 font-medium">Projects</a>
              <a href="#contact" className="text-gray-600 hover:text-blue-600 font-medium">Contact</a>
              <button className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Get Started
              </button>
            </div>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section id="home" className="pt-32 pb-20 bg-gradient-to-r from-blue-50 to-blue-100">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex flex-col md:flex-row items-center justify-between">
            <div className="md:w-1/2 mb-10 md:mb-0">
              <h1 className="text-5xl font-bold text-gray-900 mb-6">Building Your Vision, Creating Reality</h1>
              <p className="text-xl text-gray-600 mb-8">Expert construction services for residential, commercial, and infrastructure projects.</p>
              <div className="flex space-x-4">
                <button className="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                  Get Quote <ChevronRight className="ml-2" size={20} />
                </button>
                <button className="border-2 border-blue-600 text-blue-600 px-8 py-3 rounded-lg hover:bg-blue-50 transition-colors">
                  Our Projects
                </button>
              </div>
            </div>
            <div className="md:w-1/2">
              <img 
                src="/api/placeholder/600/400" 
                alt="Construction site" 
                className="rounded-xl shadow-2xl"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Services Section */}
      <section id="services" className="py-20">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">Our Services</h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">Comprehensive construction solutions tailored to your needs</p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <Card className="p-6 hover:shadow-lg transition-all hover:-translate-y-1">
              <Building size={40} className="text-blue-600 mb-4" />
              <h3 className="text-xl font-semibold mb-3">Residential Construction</h3>
              <p className="text-gray-600">Custom homes and renovations tailored to your lifestyle</p>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-all hover:-translate-y-1">
              <Warehouse size={40} className="text-blue-600 mb-4" />
              <h3 className="text-xl font-semibold mb-3">Commercial Projects</h3>
              <p className="text-gray-600">Office buildings, retail spaces, and industrial facilities</p>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-all hover:-translate-y-1">
              <Construction size={40} className="text-blue-600 mb-4" />
              <h3 className="text-xl font-semibold mb-3">Infrastructure</h3>
              <p className="text-gray-600">Roads, bridges, and public facility construction</p>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-all hover:-translate-y-1">
              <ClipboardCheck size={40} className="text-blue-600 mb-4" />
              <h3 className="text-xl font-semibold mb-3">Project Management</h3>
              <p className="text-gray-600">Expert oversight from planning to completion</p>
            </Card>
          </div>
        </div>
      </section>

      {/* Projects Section */}
      <section id="projects" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">Featured Projects</h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">Showcasing our excellence in construction</p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[1, 2, 3].map((project) => (
              <Card key={project} className="overflow-hidden hover:shadow-xl transition-all">
                <img 
                  src={`/api/placeholder/400/300`}
                  alt={`Project ${project}`}
                  className="w-full h-48 object-cover"
                />
                <div className="p-6">
                  <h3 className="text-xl font-semibold mb-2">Modern Office Complex</h3>
                  <p className="text-gray-600 mb-4">Downtown Business District</p>
                  <button className="text-blue-600 font-medium hover:text-blue-700 flex items-center">
                    View Details <ChevronRight size={20} className="ml-1" />
                  </button>
                </div>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section id="contact" className="py-20">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-2 gap-12">
            <div>
              <h2 className="text-4xl font-bold text-gray-900 mb-6">Get In Touch</h2>
              <p className="text-xl text-gray-600 mb-8">Ready to start your construction project? Contact us today.</p>
              
              <div className="space-y-6">
                <div className="flex items-center">
                  <Phone className="text-blue-600 mr-4" size={24} />
                  <div>
                    <h4 className="font-medium">Phone</h4>
                    <p className="text-gray-600">(555) 123-4567</p>
                  </div>
                </div>
                
                <div className="flex items-center">
                  <Mail className="text-blue-600 mr-4" size={24} />
                  <div>
                    <h4 className="font-medium">Email</h4>
                    <p className="text-gray-600">contact@brconstruction.com</p>
                  </div>
                </div>
                
                <div className="flex items-center">
                  <MapPin className="text-blue-600 mr-4" size={24} />
                  <div>
                    <h4 className="font-medium">Office</h4>
                    <p className="text-gray-600">123 Construction Ave, Building City</p>
                  </div>
                </div>
              </div>
            </div>
            
            <Card className="p-8">
              <form className="space-y-6">
                <div className="space-y-2">
                  <label className="block text-gray-700 font-medium">Name</label>
                  <input 
                    type="text" 
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Your name"
                  />
                </div>
                
                <div className="space-y-2">
                  <label className="block text-gray-700 font-medium">Email</label>
                  <input 
                    type="email" 
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Your email"
                  />
                </div>
                
                <div className="space-y-2">
                  <label className="block text-gray-700 font-medium">Message</label>
                  <textarea 
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    rows={4}
                    placeholder="Your message"
                  />
                </div>
                
                <button 
                  type="submit" 
                  className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Send Message
                </button>
              </form>
            </Card>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-4 gap-8">
            <div>
              <h3 className="text-xl font-bold mb-4">BR CONSTRUCTION</h3>
              <p className="text-gray-400">Building excellence since 1995</p>
            </div>
            
            <div>
              <h4 className="font-medium mb-4">Services</h4>
              <ul className="space-y-2 text-gray-400">
                <li>Residential Construction</li>
                <li>Commercial Projects</li>
                <li>Infrastructure</li>
                <li>Project Management</li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-medium mb-4">Company</h4>
              <ul className="space-y-2 text-gray-400">
                <li>About Us</li>
                <li>Our Team</li>
                <li>Careers</li>
                <li>News</li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-medium mb-4">Legal</h4>
              <ul className="space-y-2 text-gray-400">
                <li>Privacy Policy</li>
                <li>Terms of Service</li>
                <li>Cookie Policy</li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
            <p>© 2024 BR Construction. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default ConstructionWebsite;
import React from 'react';
import ReactDOM from 'react-dom/client';
import { Navigation, Hero, Services, Projects, Contact } from './components';

// Initialize all React components
const navRoot = ReactDOM.createRoot(document.getElementById('react-nav-root'));
navRoot.render(<Navigation />);

const heroRoot = ReactDOM.createRoot(document.getElementById('react-hero-root'));
heroRoot.render(<Hero />);

const servicesRoot = ReactDOM.createRoot(document.getElementById('react-services-root'));
servicesRoot.render(<Services />);

const projectsRoot = ReactDOM.createRoot(document.getElementById('react-projects-root'));
projectsRoot.render(<Projects />);

const contactRoot = ReactDOM.createRoot(document.getElementById('react-contact-root'));
contactRoot.render(<Contact />);

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});