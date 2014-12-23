function batch_genTasks( img_dir, task_dir, list_dir, task_size, path_prefix )

if ~exist(task_dir,'dir'), 
    mkdir(task_dir); 
end

if ~exist(list_dir,'dir'), 
    mkdir(list_dir); 
end

if ~exist('task_size','var') || isempty(task_size), 
    task_size = 50;
end

if ~exist('path_prefix','var'), 
    path_prefix = img_dir;
end

subdirs =  dir(img_dir);
categories = cell(0);
for i=1:length(subdirs),
    if subdirs(i).isdir && subdirs(i).name(1)~='.',
        categories{end+1} = subdirs(i).name;
    end
end

fid_l = fopen(fullfile(list_dir,'categories.txt'),'w+');
for c=1:length(categories),
    fprintf('.');
    files = dir(fullfile(img_dir,categories{c},'*.png'));
    names = {files.name};
    nAll = length(names);
    names = names(randperm(nAll));
    nTask = ceil(nAll/task_size);
    for t=1:nTask, 
        if t==nTask && mod(nAll,task_size)~=0, nImg = mod(nAll,task_size); else nImg = task_size; end
        fid = fopen(fullfile(task_dir,[categories{c} '-' int2str(t)]),'w+');
        for i=1:nImg, 
            fprintf(fid,'%s\n',fullfile(path_prefix,categories{c},names{(t-1)*task_size+i}));
        end
        fclose(fid);
    end
    fprintf(fid_l,'%s\n',categories{c});
    if mod(c,20)==0, fprintf('\t%d/%d\n',c,length(categories)); end
end
fclose(fid_l);

end

