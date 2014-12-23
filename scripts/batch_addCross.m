function batch_addCross( img_dir, output_dir, cross_file )

if ~exist(output_dir,'dir'), 
    mkdir(output_dir); 
end

cross_img = imread(cross_file);

subdirs =  dir(img_dir);
categories = cell(0);
for i=1:length(subdirs),
    if subdirs(i).isdir && subdirs(i).name(1)~='.',
        categories{end+1} = subdirs(i).name;
    end
end
for c=1:length(categories),
    if ~exist(fullfile(output_dir,categories{c}),'dir'), 
        mkdir(fullfile(output_dir,categories{c})); 
    end
    fprintf('.');
    files = dir(fullfile(img_dir,categories{c},'*.png'));
    for i=1:length(files), 
        imwrite(addCross(imread(fullfile(img_dir,categories{c},files(i).name)),...
            cross_img),fullfile(output_dir,categories{c},files(i).name));
    end
    if mod(c,20)==0, fprintf('\t%d/%d\n',c,length(categories)); end
end

end

