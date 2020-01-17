# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

    config.vm.box = "scotch/box-pro"
    config.vm.hostname = "scotchbox"
    config.vm.network "forwarded_port", guest: 80, host: 8080
    config.vm.network "private_network", ip: "192.168.33.19"
    
    if Vagrant::Util::Platform.windows?
        # Optional NFS. Make sure to remove other synced_folder line too
        config.vm.synced_folder ".", "/var/www", :nfs => { :mount_options => ["dmode=777","fmode=666"] }
    else
        config.vm.synced_folder ".", "/var/www", :mount_options => ["dmode=777", "fmode=666"]
    end

    # Performance optimisation
    config.vm.provider "virtualbox" do |vb|
        # Customize the amount of memory on the VM:
        vb.memory = "4096"
        vb.cpus = "2"

        # Enabling multiple cores in Vagrant/VirtualBox
        vb.customize ["modifyvm", :id, "--ioapic", "on"]

        # change the network card hardware for better performance
        vb.customize ["modifyvm", :id, "--nictype1", "virtio" ]
        vb.customize ["modifyvm", :id, "--nictype2", "virtio" ]

        # suggested fix for slow network performance
        # see https://github.com/mitchellh/vagrant/issues/1807
        vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
    end

    # Windows Support
    if Vagrant::Util::Platform.windows?
        config.vm.provision "shell",
        inline: "
            sudo apt-get install -y dos2unix && 
            cd /var/www/provision/shell &&
            find . -type f -name '*.sh' -print0 | xargs -0 dos2unix &&
            cd /var/www/provision/apache &&
            find . -type f -name '*.conf' -print0 | xargs -0 dos2unix
        ",
        run: "always", privileged: false
    end

    config.vm.provision "shell" do |s|
        ssh_prv_key = ""
        ssh_pub_key = ""
        if File.file?("#{Dir.home}/.ssh/id_rsa")
            ssh_config = File.read("#{Dir.home}/.ssh/config")
            ssh_prv_key = File.read("#{Dir.home}/.ssh/id_rsa")
            ssh_pub_key = File.readlines("#{Dir.home}/.ssh/id_rsa.pub").first.strip
        else
            puts "No SSH key found. You will need to remedy this before pushing to the repository."
        end
        s.inline = <<-SHELL
            if grep -sq "#{ssh_pub_key}" /home/vagrant/.ssh/authorized_keys; then
                echo "SSH keys already provisioned."
                exit 0;
            fi
            echo "SSH key provisioning."
            echo #{ssh_pub_key} >> /home/vagrant/.ssh/authorized_keys
            echo #{ssh_pub_key} > /home/vagrant/.ssh/id_rsa.pub
            chmod 644 /home/vagrant/.ssh/id_rsa.pub
            echo "#{ssh_prv_key}" > /home/vagrant/.ssh/id_rsa
            chmod 600 /home/vagrant/.ssh/id_rsa
            echo "#{ssh_config}" > /home/vagrant/.ssh/config
            chown -R vagrant:vagrant /home/vagrant
            exit 0
        SHELL
    end

    config.vm.provision "shell", path: "provision/shell/provision--root.sh", keep_color: true
    config.vm.provision "shell", path: "provision/shell/provision--vagrant.sh", privileged: false, keep_color: true
end